<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Auth\Security\PasswordHasherInterface;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Issue\Entity\Issue;
use App\Domain\Issue\Entity\IssueActivity;
use App\Domain\Issue\Entity\IssueAttachment;
use App\Domain\Issue\Entity\IssueChecklistItem;
use App\Domain\Issue\Entity\IssueComment;
use App\Domain\Issue\Entity\IssueLink;
use App\Domain\Issue\Entity\IssueWorklog;
use App\Domain\Planning\Entity\Epic;
use App\Domain\Planning\Entity\Sprint;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Entity\ProjectMember;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:seed', description: 'Seed initial data: roles, permissions, admin user and sample catalog data')]
class DataInitializer extends Command
{
    private const string DEMO_ATTACHMENT_FILENAME = 'lKs5G8TylIB9d9THDVWENHddoKEKXLEoOhj2mbWh.png';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasherInterface $hasher,
        private readonly FileStorageInterface $storage,
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
        #[Autowire('%kernel.project_dir%/fixtures/demo/attachments/DEMO-4/lKs5G8TylIB9d9THDVWENHddoKEKXLEoOhj2mbWh.png')]
        private readonly string $demoAttachmentFixture,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Re-seed even if data already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ('prod' === $this->environment) {
            $io->error('Demo data cannot be seeded in production.');

            return Command::FAILURE;
        }
        $force = $input->getOption('force');

        $io->title('Boilerplate DDD — Database Seeder');

        if ($force) {
            // This new project is Planner-first; clear sample commerce rows left
            // by the DDD reference template from the first local boot.
            $connection = $this->em->getConnection();
            $connection->executeStatement('DELETE FROM order_lines');
            $connection->executeStatement('DELETE FROM orders');
            $connection->executeStatement('DELETE FROM products');
            $connection->executeStatement('DELETE FROM customers');
        }

        // ── Auth ──────────────────────────────────────────────────────────────
        $io->section('Roles & Permissions');

        $permDefs = [
            'users:read' => 'Read users',
            'users:write' => 'Write users',
            'users:delete' => 'Delete users',
            'roles:read' => 'Read roles',
            'roles:write' => 'Manage roles',
        ];

        $perms = [];
        foreach ($permDefs as $name => $desc) {
            $perm = $this->em->getRepository(Permission::class)->findOneBy(['name' => $name]);
            if (null === $perm) {
                $perm = new Permission();
                $perm->setName($name);
                $perm->setDescription($desc);
                $this->em->persist($perm);
                $io->writeln("  <fg=green>+</> Permission: $name");
            }
            $perms[$name] = $perm;
        }

        $this->em->flush();

        $adminRole = $this->em->getRepository(Role::class)->findOneBy(['name' => 'ADMIN']);
        if (null === $adminRole) {
            $adminRole = new Role();
            $adminRole->setName('ADMIN');
            $adminRole->setDescription('Administrator role with full access');
            $adminRole->setPermissions(array_values($perms));
            $this->em->persist($adminRole);
            $io->writeln('  <fg=green>+</> Role: ADMIN');
        }

        $userRole = $this->em->getRepository(Role::class)->findOneBy(['name' => 'USER']);
        if (null === $userRole) {
            $userRole = new Role();
            $userRole->setName('USER');
            $userRole->setDescription('Standard user role');
            $userRole->setPermissions([$perms['users:read']]);
            $this->em->persist($userRole);
            $io->writeln('  <fg=green>+</> Role: USER');
        }

        $this->em->flush();

        $demoUsers = [
            ['Laura', 'Gómez', 'laura@demo.local'],
            ['Marcos', 'Ruiz', 'marcos@demo.local'],
            ['Elena', 'Torres', 'elena@demo.local'],
        ];
        foreach ($demoUsers as [$firstName, $lastName, $email]) {
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (null !== $existingUser) {
                if ('laura@demo.local' === $email) {
                    $existingUser->setAvatarUrl('/demo/avatars/2/AcO9YLKJJXoBPn96PV7krjodUW2aLdl7gGDVIddo.png');
                } elseif ('marcos@demo.local' === $email) {
                    $existingUser->setAvatarUrl('/demo/avatars/1/U2zoDg4MmhnEMJjoYVtXAdeg7KKTIVJf50H3qdYl.png');
                }
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPasswordHash($this->hasher->hash($user, 'demo123'));
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles([$userRole]);
            if ('laura@demo.local' === $email) {
                $user->setAvatarUrl('/demo/avatars/2/AcO9YLKJJXoBPn96PV7krjodUW2aLdl7gGDVIddo.png');
            } elseif ('marcos@demo.local' === $email) {
                $user->setAvatarUrl('/demo/avatars/1/U2zoDg4MmhnEMJjoYVtXAdeg7KKTIVJf50H3qdYl.png');
            }
            $this->em->persist($user);
        }
        $this->em->flush();

        $io->section('Admin User');

        $adminEmail = 'admin@admin.com';
        $existingAdmin = $this->em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
        if (null !== $existingAdmin) {
            $existingAdmin->setAvatarUrl('/demo/avatars/1/nqyaBorOubooKHaGuYkaLf3bB8PHZEP1zDxrG0KC.png');
        }
        if (null === $existingAdmin || $force) {
            if (null === $existingAdmin) {
                $admin = new User();
                $admin->setEmail($adminEmail);
                $admin->setPasswordHash($this->hasher->hash($admin, 'admin123'));
                $admin->setFirstName('Admin');
                $admin->setLastName('User');
                $admin->setRoles([$adminRole]);
                $this->em->persist($admin);
                $io->writeln("  <fg=green>+</> $adminEmail / admin123");
            } else {
                $io->writeln('  <fg=yellow>~</> Admin already exists (use --force to reset password)');
            }
        }

        $this->em->flush();

        // ── Planner demo ───────────────────────────────────────────────────────
        $io->section('Planner demo projects and issues');
        if ($force) {
            foreach ([IssueActivity::class, IssueAttachment::class, IssueChecklistItem::class, IssueComment::class, IssueLink::class, IssueWorklog::class] as $dependentClass) {
                foreach ($this->em->getRepository($dependentClass)->findAll() as $dependent) {
                    $this->em->remove($dependent);
                }
            }
            foreach ($this->em->getRepository(Issue::class)->findAll() as $issue) {
                $this->em->remove($issue);
            }
            foreach ($this->em->getRepository(Project::class)->findAll() as $project) {
                $this->em->remove($project);
            }
            foreach ($this->em->getRepository(ProjectMember::class)->findAll() as $member) {
                $this->em->remove($member);
            }
            foreach ($this->em->getRepository(Epic::class)->findAll() as $epic) {
                $this->em->remove($epic);
            }
            foreach ($this->em->getRepository(Sprint::class)->findAll() as $sprint) {
                $this->em->remove($sprint);
            }
            $this->em->flush();
        }

        if (0 === $this->em->getRepository(Project::class)->count([])) {
            $projectDefinitions = [
                ['DEMO', 'Demo Project', 'Proyecto de demostración con issues de ejemplo.', '/demo/project-nova.svg'],
            ];
            /** @var array<string, Project> $projects */
            $projects = [];
            foreach ($projectDefinitions as [$key, $name, $description, $cover]) {
                $project = Project::create($name, $key, $description, $cover, '#6366f1', new DateTimeImmutable('2026-06-29'), new DateTimeImmutable('2026-10-01'));
                $this->em->persist($project);
                $projects[$key] = $project;
            }
            $this->em->flush();

            foreach ([
                ['admin@admin.com', 'owner'],
                ['laura@demo.local', 'member'],
                ['marcos@demo.local', 'member'],
                ['elena@demo.local', 'member'],
            ] as [$email, $role]) {
                $member = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
                if (null !== $member) {
                    $this->em->persist(new ProjectMember((int) $projects['DEMO']->getId(), $member->requireId(), $role));
                }
            }

            $epicDefinitions = [
                ['DEMO', 'DEMO-E1', 'Autenticación y usuarios', 'Todo lo relacionado con login, registro y perfiles.', 'in_progress', 50],
                ['DEMO', 'DEMO-E2', 'Cimientos técnicos', 'Infraestructura base del proyecto: CI/CD, base de datos y SEO.', 'done', 100],
                ['DEMO', 'DEMO-E3', 'Estabilización y documentación', 'Refactors, correcciones y documentación de la primera versión estable.', 'in_progress', 65],
                ['DEMO', 'DEMO-E4', 'Métricas e integraciones', 'Dashboard de métricas del proyecto e integraciones externas.', 'open', 0],
            ];
            /** @var array<string, Epic> $epics */
            $epics = [];
            foreach ($epicDefinitions as [$projectKey, $epicKey, $name, $description, $status, $progress]) {
                $epic = new Epic((int) $projects[$projectKey]->getId(), $epicKey, $name, $description, $status, $progress);
                $this->em->persist($epic);
                $epics[$name] = $epic;
            }

            $sprintDefinitions = [
                ['Sprint -2 (histórico)', 'Cimientos técnicos del proyecto.', 'completed', '2026-06-18', '2026-07-01'],
                ['Sprint -1 (histórico)', 'Estabilización y documentación.', 'completed', '2026-07-02', '2026-07-15'],
                ['Sprint 1', 'Completar el módulo de autenticación.', 'active', '2026-07-30', '2026-08-13'],
                ['Sprint 2 (planificación)', 'Métricas e integraciones.', 'planning', '2026-08-14', '2026-08-27'],
            ];
            /** @var array<string, Sprint> $sprints */
            $sprints = [];
            foreach ($sprintDefinitions as [$name, $goal, $status, $start, $end]) {
                $sprint = new Sprint((int) $projects['DEMO']->getId(), $name, $goal, $status, new DateTimeImmutable($start), new DateTimeImmutable($end));
                $this->em->persist($sprint);
                $sprints[$name] = $sprint;
            }

            $this->em->flush();

            $issueDefinitions = [
                ['DEMO-1', 'Diseñar pantalla de login', 'done', 'high', '2026-08-02', 'Autenticación y usuarios', 'Sprint 1', 'story', 3, ['Admin User']],
                ['DEMO-2', 'Implementar autenticación JWT', 'in_progress', 'critical', '2026-08-10', 'Autenticación y usuarios', 'Sprint 1', 'task', 8, ['Admin User', 'Marcos Ruiz']],
                ['DEMO-3', 'Corregir bug en recuperación de contraseña', 'in_review', 'high', '2026-08-06', 'Autenticación y usuarios', 'Sprint 1', 'bug', 2, ['Elena Torres']],
                ['DEMO-4', 'Crear perfil de usuario', 'todo', 'medium', '2026-08-03', 'Autenticación y usuarios', 'Sprint 1', 'story', 5, ['Laura Gómez', 'Marcos Ruiz']],
                ['DEMO-5', 'Tests unitarios de autenticación', 'done', 'low', '2026-08-04', 'Autenticación y usuarios', 'Sprint 1', 'task', 3, ['Laura Gómez']],
                ['DEMO-6', 'Configurar CI/CD', 'done', 'medium', '2026-06-22', 'Cimientos técnicos', 'Sprint -2 (histórico)', 'task', 3, ['Admin User']],
                ['DEMO-7', 'Añadir favicon y metadatos SEO', 'done', 'low', '2026-06-21', 'Cimientos técnicos', 'Sprint -2 (histórico)', 'task', 1, ['Admin User']],
                ['DEMO-8', 'Migrar base de datos a UTF8MB4', 'done', 'high', '2026-06-27', 'Cimientos técnicos', 'Sprint -2 (histórico)', 'task', 5, ['Admin User', 'Marcos Ruiz']],
                ['DEMO-9', 'Refactor del servicio de notificaciones', 'done', 'medium', '2026-07-08', 'Estabilización y documentación', 'Sprint -1 (histórico)', 'task', 5, ['Marcos Ruiz']],
                ['DEMO-10', 'Bug: doble envío de email de bienvenida', 'done', 'critical', '2026-07-09', 'Estabilización y documentación', 'Sprint -1 (histórico)', 'bug', 2, ['Admin User']],
                ['DEMO-11', 'Documentar API interna', 'done', 'low', '2026-07-14', 'Estabilización y documentación', 'Sprint -1 (histórico)', 'task', 3, ['Elena Torres']],
                ['DEMO-12', 'Mejorar rendimiento de listado de proyectos', 'todo', 'medium', '2026-07-15', 'Estabilización y documentación', 'Sprint -1 (histórico)', 'task', 3, ['Admin User']],
                ['DEMO-13', 'Diseñar dashboard de métricas', 'todo', 'medium', '2026-08-20', 'Métricas e integraciones', 'Sprint 2 (planificación)', 'story', 5, []],
                ['DEMO-14', 'Investigar integración con Slack', 'todo', 'low', '2026-08-24', 'Métricas e integraciones', 'Sprint 2 (planificación)', 'task', 2, []],
            ];
            $issueStartDates = [
                'DEMO-1' => '2026-07-30', 'DEMO-2' => '2026-07-31', 'DEMO-3' => '2026-07-31', 'DEMO-4' => '2026-07-30', 'DEMO-5' => '2026-08-03',
                'DEMO-6' => '2026-06-18', 'DEMO-7' => '2026-06-20', 'DEMO-8' => '2026-06-22', 'DEMO-9' => '2026-07-02', 'DEMO-10' => '2026-07-08',
                'DEMO-11' => '2026-07-09', 'DEMO-12' => '2026-07-11', 'DEMO-13' => null, 'DEMO-14' => null,
            ];
            $issueDescriptions = [
                'DEMO-2' => '<p><strong>Contexto</strong></p><p>Validar la emisión y renovación de tokens en el flujo de autenticación.</p><p><strong>Criterios de aceptación</strong></p><ul><li>El token de acceso expira en el tiempo configurado.</li><li>El refresh token permite renovar la sesión.</li><li>Los errores de expiración se muestran claramente.</li></ul>',
                'DEMO-4' => '<p>Diseñar y completar el perfil de usuario, incluyendo la carga del avatar.</p><p><strong>Captura de referencia</strong></p><p><img src="/demo/attachments/DEMO-4/lKs5G8TylIB9d9THDVWENHddoKEKXLEoOhj2mbWh.png" alt="Captura de referencia para la tarea de perfil" /></p><p>Selecciona la imagen en modo edición para reemplazarla o quitarla.</p>',
            ];
            /** @var array<string, Issue> $issues */
            $issues = [];
            foreach ($issueDefinitions as [$key, $title, $status, $priority, $dueAt, $epicName, $sprintName, $type, $storyPoints, $assignees]) {
                $dueDate = new DateTimeImmutable($dueAt);
                $issue = new Issue(
                    (int) $projects['DEMO']->getId(), $key, $title, $status, $priority,
                    $dueDate, (int) $epics[$epicName]->getId(), (int) $sprints[$sprintName]->getId(),
                    $type, $storyPoints, 'Admin User', $assignees, $dueDate->modify('-3 days'), $issueDescriptions[$key] ?? null,
                    isset($issueStartDates[$key]) ? new DateTimeImmutable($issueStartDates[$key]) : null,
                );
                $this->em->persist($issue);
                $issues[$key] = $issue;
            }
            $this->em->flush();

            foreach (['DEMO-1', 'DEMO-2', 'DEMO-3', 'DEMO-4', 'DEMO-5'] as $key) {
                $this->em->persist(new IssueComment((int) $issues[$key]->getId(), 'Admin User', 'Este issue fue creado como ejemplo en el seeder inicial.', new DateTimeImmutable('2026-08-01')));
            }
            $this->em->persist(new IssueComment((int) $issues['DEMO-2']->getId(), 'Admin User', 'Marcos Ruiz, ¿puedes revisar el middleware de verificación cuando tengas un hueco?', new DateTimeImmutable('2026-08-03')));

            foreach ([
                ['Generar y firmar el token', true],
                ['Middleware de verificación', true],
                ['Refresh token', false],
                ['Tests de expiración', false],
            ] as $position => [$text, $isDone]) {
                $this->em->persist(new IssueChecklistItem((int) $issues['DEMO-2']->getId(), $text, $isDone, $position));
            }
            foreach ([
                ['Formulario de edición', false],
                ['Subida de avatar', false],
            ] as $position => [$text, $isDone]) {
                $this->em->persist(new IssueChecklistItem((int) $issues['DEMO-4']->getId(), $text, $isDone, $position));
            }

            $this->em->persist(new IssueWorklog((int) $issues['DEMO-2']->getId(), 'Admin User', 120, 'Investigación de librerías JWT', new DateTimeImmutable('2026-08-01T10:00:00')));
            $this->em->persist(new IssueWorklog((int) $issues['DEMO-2']->getId(), 'Marcos Ruiz', 90, 'Implementación inicial del middleware', new DateTimeImmutable('2026-08-02T14:00:00')));
            $this->em->persist(new IssueLink((int) $issues['DEMO-2']->getId(), (int) $issues['DEMO-4']->getId(), 'blocks'));

            $attachment = new IssueAttachment((int) $issues['DEMO-4']->getId(), self::DEMO_ATTACHMENT_FILENAME, 'LISTROWS.png', 'image/png', 10122756);
            $this->em->persist($attachment);

            foreach ([
                ['DEMO-1', 'todo', 'in_progress', '2026-07-31'], ['DEMO-1', 'in_progress', 'done', '2026-08-02'],
                ['DEMO-2', 'todo', 'in_progress', '2026-08-01'],
                ['DEMO-3', 'todo', 'in_progress', '2026-07-31'], ['DEMO-3', 'in_progress', 'in_review', '2026-08-03'],
                ['DEMO-5', 'todo', 'in_progress', '2026-08-03'], ['DEMO-5', 'in_progress', 'done', '2026-08-04'],
            ] as [$issueKey, $oldValue, $newValue, $createdAt]) {
                $this->em->persist(new IssueActivity((int) $issues[$issueKey]->getId(), 'Admin User', 'status', $oldValue, $newValue, new DateTimeImmutable($createdAt)));
            }
            $this->em->flush();

            $io->writeln('  <fg=green>+</> DEMO project, 14 issues, 4 epics and 4 sprints with historical reporting data');
        } else {
            $io->writeln('  <fg=yellow>~</> Planner projects already exist, skipping');
        }

        $this->ensureDemoAttachmentIsPrivate();

        $io->success([
            'Seed complete.',
            'Admin: admin@admin.com / admin123',
            'Demo users: laura@demo.local, marcos@demo.local, elena@demo.local / demo123',
            'Roles: ADMIN (full), USER (read-only)',
            'Permissions: users:read, users:write, users:delete, roles:read, roles:write',
            'Commercial catalog seeding is disabled for the planner demo.',
            'Planner demo: DEMO project, 14 issues, 4 epics and 4 sprints based on the project-management acceptance data.',
        ]);

        return Command::SUCCESS;
    }

    private function ensureDemoAttachmentIsPrivate(): void
    {
        $issue = $this->em->getRepository(Issue::class)->findOneBy(['issueKey' => 'DEMO-4']);
        if (!$issue instanceof Issue || null === $issue->getId()) {
            return;
        }

        $attachment = $this->em->getRepository(IssueAttachment::class)->findOneBy([
            'issueId' => $issue->getId(),
            'filename' => self::DEMO_ATTACHMENT_FILENAME,
        ]);
        if (!$attachment instanceof IssueAttachment || null === $attachment->getId()) {
            return;
        }

        $directory = 'issue-attachments/'.$issue->getIssueKey();
        if (!$this->storage->exists($directory, self::DEMO_ATTACHMENT_FILENAME)) {
            $this->storage->copy($directory, self::DEMO_ATTACHMENT_FILENAME, $this->demoAttachmentFixture);
        }

        $publicUrl = '/demo/attachments/DEMO-4/'.self::DEMO_ATTACHMENT_FILENAME;
        $description = $issue->getDescription() ?? '';
        if (!str_contains($description, $publicUrl)) {
            return;
        }

        $description = str_replace(
            $publicUrl,
            \sprintf('/api/issues/%d/attachments/%d', $issue->getId(), $attachment->getId()),
            $description,
        );
        $issue->updateDetails(
            $issue->getTitle(),
            $issue->getType(),
            $issue->getPriority(),
            $description,
            $issue->getDueAt(),
            $issue->getEpicId(),
            $issue->getSprintId(),
            $issue->getStoryPoints(),
            $issue->getAssigneeNames(),
        );
        $this->em->flush();
    }
}
