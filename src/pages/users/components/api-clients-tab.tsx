import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Plus, Copy, Check, Trash2, KeyRound, ShieldOff } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { ConfirmDialog } from '@/components/dialogs/confirm-dialog'
import { useI18n } from '@/i18n/context'
import { useCopyToClipboard } from '@/hooks/use-copy-to-clipboard'
import {
  apiClientsApi,
  AVAILABLE_SCOPES,
  type ApiClient,
  type CreateApiClientPayload,
  type CreateApiClientResult,
} from '@/services/api-clients-api'

function CopyButton({ text }: { text: string }) {
  const { copied, copy } = useCopyToClipboard()
  return (
    <button
      type="button"
      onClick={() => copy(text)}
      className="ml-1 inline-flex items-center text-muted-foreground hover:text-foreground"
    >
      {copied ? <Check className="h-3.5 w-3.5 text-green-500" /> : <Copy className="h-3.5 w-3.5" />}
    </button>
  )
}

interface CreatedClientViewProps {
  clientId: string
  secret: string
  onClose: () => void
}

function CreatedClientView({ clientId, secret, onClose }: CreatedClientViewProps) {
  const { t } = useI18n()
  return (
    <Dialog open onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <KeyRound className="h-5 w-5 text-green-500" />
            {t('created_client_title')}
          </DialogTitle>
          <DialogDescription>{t('created_client_secret_warning')}</DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label className="text-xs text-muted-foreground">Client ID</Label>
            <div className="flex items-center rounded-md border bg-muted px-3 py-2 font-mono text-sm">
              {clientId}
              <CopyButton text={clientId} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs text-muted-foreground">Client Secret</Label>
            <div className="flex items-center rounded-md border border-yellow-400 bg-yellow-50 px-3 py-2 font-mono text-sm dark:bg-yellow-950">
              <span className="break-all">{secret}</span>
              <CopyButton text={secret} />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button onClick={onClose}>{t('created_client_understood')}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function CreateClientDialog({
  open,
  onOpenChange,
}: {
  open: boolean
  onOpenChange: (v: boolean) => void
}) {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [scopes, setScopes] = useState<string[]>([])
  const [result, setResult] = useState<{ clientId: string; secret: string } | null>(null)

  const { mutate, isPending } = useMutation({
    mutationFn: (payload: CreateApiClientPayload) => apiClientsApi.create(payload),
    onSuccess: (data: CreateApiClientResult) => {
      queryClient.invalidateQueries({ queryKey: ['api-clients'] })
      setResult({ clientId: data.client.clientId, secret: data.secret })
    },
    onError: () => toast.error(t('create_api_client_error')),
  })

  const handleClose = () => {
    setName('')
    setScopes([])
    setResult(null)
    onOpenChange(false)
  }

  const toggleScope = (value: string) => {
    if (value === '*') {
      setScopes(scopes.includes('*') ? [] : ['*'])
      return
    }
    setScopes((prev) =>
      prev.includes(value)
        ? prev.filter((s) => s !== value)
        : [...prev.filter((s) => s !== '*'), value],
    )
  }

  if (result) {
    return (
      <CreatedClientView clientId={result.clientId} secret={result.secret} onClose={handleClose} />
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('create_api_client_title')}</DialogTitle>
          <DialogDescription>{t('create_api_client_description')}</DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="client-name">{t('create_api_client_system_name')}</Label>
            <Input
              id="client-name"
              placeholder={t('create_api_client_system_name_placeholder') ?? ''}
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label>{t('create_api_client_scopes_label')}</Label>
            <div className="space-y-2 rounded-md border p-3">
              {AVAILABLE_SCOPES.map((scope) => (
                <div key={scope.value} className="flex items-center gap-2">
                  <Checkbox
                    id={`scope-${scope.value}`}
                    checked={scopes.includes(scope.value)}
                    onCheckedChange={() => toggleScope(scope.value)}
                  />
                  <label htmlFor={`scope-${scope.value}`} className="cursor-pointer text-sm">
                    {scope.label}
                  </label>
                </div>
              ))}
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            {t('create_api_client_cancel')}
          </Button>
          <Button
            onClick={() => {
              if (name.trim() && scopes.length > 0) mutate({ name: name.trim(), scopes })
            }}
            disabled={isPending || !name.trim() || scopes.length === 0}
          >
            {isPending ? t('create_api_client_creating') : t('create_api_client_submit')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export function ApiClientsTab() {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [createOpen, setCreateOpen] = useState(false)
  const [revokeTarget, setRevokeTarget] = useState<ApiClient | null>(null)

  const { data: clients = [], isLoading } = useQuery({
    queryKey: ['api-clients'],
    queryFn: () => apiClientsApi.list(),
  })

  const { mutate: revoke } = useMutation({
    mutationFn: (id: string) => apiClientsApi.revoke(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['api-clients'] })
      toast.success(t('api_clients_revoked_ok'))
      setRevokeTarget(null)
    },
    onError: () => toast.error(t('api_clients_revoke_error')),
  })

  return (
    <>
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>{t('api_clients_title')}</CardTitle>
              <CardDescription>{t('api_clients_description')}</CardDescription>
            </div>
            <Button size="sm" className="gap-1" onClick={() => setCreateOpen(true)}>
              <Plus className="h-4 w-4" />
              {t('api_clients_new')}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="py-8 text-center text-sm text-muted-foreground">{t('common_loading')}</p>
          ) : clients.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted-foreground">
              {t('api_clients_empty')}
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t('api_clients_col_name')}</TableHead>
                  <TableHead>{t('api_clients_col_client_id')}</TableHead>
                  <TableHead>{t('api_clients_col_scopes')}</TableHead>
                  <TableHead>{t('api_clients_col_status')}</TableHead>
                  <TableHead>{t('api_clients_col_last_used')}</TableHead>
                  <TableHead className="text-right">{t('api_clients_col_actions')}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {clients.map((client) => (
                  <TableRow key={client.id}>
                    <TableCell className="font-medium">{client.name}</TableCell>
                    <TableCell>
                      <span className="font-mono text-xs text-muted-foreground">
                        {client.clientId}
                      </span>
                      <CopyButton text={client.clientId} />
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-wrap gap-1">
                        {client.scopes.map((s) => (
                          <Badge key={s} variant="secondary" className="text-xs">
                            {s}
                          </Badge>
                        ))}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={client.active ? 'default' : 'destructive'}>
                        {client.active
                          ? t('api_clients_status_active')
                          : t('api_clients_status_revoked')}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {client.lastUsedAt
                        ? new Date(client.lastUsedAt).toLocaleString()
                        : t('api_clients_last_used_never')}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive"
                        disabled={!client.active}
                        onClick={() => setRevokeTarget(client)}
                      >
                        {client.active ? (
                          <Trash2 className="h-4 w-4" />
                        ) : (
                          <ShieldOff className="h-4 w-4" />
                        )}
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <CreateClientDialog open={createOpen} onOpenChange={setCreateOpen} />

      <ConfirmDialog
        open={!!revokeTarget}
        onOpenChange={(open) => {
          if (!open) setRevokeTarget(null)
        }}
        title={t('api_clients_revoke_title') ?? ''}
        desc={t('api_clients_revoke_desc', { name: revokeTarget?.name ?? '' }) ?? ''}
        confirmText={t('api_clients_revoke_confirm') ?? ''}
        destructive
        handleConfirm={() => revokeTarget && revoke(revokeTarget.id)}
      />
    </>
  )
}
