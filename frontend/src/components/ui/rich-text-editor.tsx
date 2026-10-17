import {
  useEditor,
  EditorContent,
  NodeViewWrapper,
  ReactNodeViewRenderer,
  type NodeViewProps,
} from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Mention from '@tiptap/extension-mention'
import { SuggestionProps, SuggestionKeyDownProps } from '@tiptap/suggestion'
import type { AnyExtension } from '@tiptap/core'
import { useCallback, useMemo, useRef, useState } from 'react'
import {
  IconBold,
  IconItalic,
  IconCode,
  IconList,
  IconListNumbers,
  IconBlockquote,
  IconPhoto,
  IconStrikethrough,
  IconRefresh,
  IconTrash,
} from '@tabler/icons-react'
import { Separator } from '@/components/ui/separator'
import { cn } from '@/lib/utils'
import { toast } from 'sonner'
import { useI18n } from '@/i18n/context'
import { uploadsApi, type UploadedAttachment } from '@/services/uploads-api'

// ── Image NodeView ────────────────────────────────────────────────────────────

function ImageNodeView({ node, selected, updateAttributes, deleteNode }: NodeViewProps) {
  const src = (node.attrs.src as string | undefined) ?? ''
  const alt = (node.attrs.alt as string | undefined) ?? ''
  const replaceInputRef = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)
  const { t } = useI18n()

  async function handleReplace(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    e.target.value = ''
    const issueId = src.match(
      /^\/api\/issues\/(\d+)\/(?:attachments\/\d+|legacy-uploads\/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp))$/i,
    )?.[1]
    if (!issueId) {
      toast.error(t('planner_editor_save_issue_before_image_upload'))
      return
    }

    setUploading(true)
    try {
      const result = await uploadsApi.image(Number(issueId), file)
      updateAttributes({ src: result.url })
      toast.success(t('planner_editor_image_replaced'))
    } catch {
      toast.error(t('planner_editor_image_upload_error'))
    } finally {
      setUploading(false)
    }
  }

  return (
    <NodeViewWrapper className="relative inline-block w-full my-2 group">
      <img
        src={src}
        alt={alt}
        className={cn(
          'rounded-md max-h-80 object-contain border transition-all',
          selected && 'ring-2 ring-primary ring-offset-1',
        )}
      />

      {/* Overlay controls — visible when selected or on hover */}
      <div
        className={cn(
          'absolute top-2 right-2 flex gap-1 transition-opacity',
          selected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100',
        )}
      >
        <button
          type="button"
          title={t('planner_editor_image_replace')}
          onClick={() => replaceInputRef.current?.click()}
          disabled={uploading}
          className="flex items-center gap-1 px-2 py-1 rounded text-xs bg-background/90 border shadow-sm hover:bg-muted transition-colors"
        >
          <IconRefresh className={cn('h-3 w-3', uploading && 'animate-spin')} />
          {uploading ? t('planner_editor_image_uploading') : t('planner_editor_image_change')}
        </button>
        <button
          type="button"
          title={t('planner_editor_image_remove')}
          onClick={deleteNode}
          className="flex items-center gap-1 px-2 py-1 rounded text-xs bg-background/90 border shadow-sm hover:bg-destructive hover:text-white hover:border-destructive transition-colors"
        >
          <IconTrash className="h-3 w-3" />
          {t('planner_editor_image_remove_button')}
        </button>
      </div>

      <input
        ref={replaceInputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={handleReplace}
      />
    </NodeViewWrapper>
  )
}

// Custom Image extension with NodeView
const InteractiveImage = Image.extend({
  addNodeView() {
    return ReactNodeViewRenderer(ImageNodeView)
  },
})

// ── Mentions ──────────────────────────────────────────────────────────────────

export interface MentionableUser {
  id: number
  name: string
}

interface MentionCommandAttrs {
  id: number
  label: string
}

function createMentionSuggestion(users: MentionableUser[], noMatchesLabel: string) {
  return {
    items: ({ query }: { query: string }) =>
      users.filter((u) => u.name.toLowerCase().includes(query.toLowerCase())).slice(0, 8),

    render: () => {
      let popup: HTMLDivElement | null = null
      let currentItems: MentionableUser[] = []
      let selectedIndex = 0
      let command: (attrs: MentionCommandAttrs) => void = () => {}

      function select(index: number) {
        const item = currentItems[index]
        if (item) command({ id: item.id, label: item.name })
      }

      function renderItems() {
        if (!popup) return
        popup.innerHTML = ''
        if (currentItems.length === 0) {
          const empty = document.createElement('div')
          empty.className = 'px-2 py-1.5 text-xs text-muted-foreground'
          empty.textContent = noMatchesLabel
          popup.appendChild(empty)
          return
        }
        currentItems.forEach((item, i) => {
          const el = document.createElement('div')
          el.textContent = item.name
          el.className = cn(
            'px-2 py-1.5 text-sm rounded cursor-pointer',
            i === selectedIndex ? 'bg-accent text-accent-foreground' : 'text-foreground',
          )
          el.addEventListener('mousedown', (e) => {
            e.preventDefault()
            select(i)
          })
          popup?.appendChild(el)
        })
      }

      function position(clientRect?: (() => DOMRect | null) | null) {
        if (!popup) return
        const rect = clientRect?.()
        if (!rect) return
        popup.style.left = `${rect.left + window.scrollX}px`
        popup.style.top = `${rect.bottom + window.scrollY + 4}px`
      }

      return {
        onStart: (props: SuggestionProps<MentionableUser>) => {
          currentItems = props.items
          command = props.command as (attrs: MentionCommandAttrs) => void
          selectedIndex = 0
          popup = document.createElement('div')
          popup.className =
            'fixed z-50 min-w-40 max-w-64 rounded-md border bg-popover shadow-md p-1'
          document.body.appendChild(popup)
          renderItems()
          position(props.clientRect)
        },
        onUpdate: (props: SuggestionProps<MentionableUser>) => {
          currentItems = props.items
          command = props.command as (attrs: MentionCommandAttrs) => void
          selectedIndex = 0
          renderItems()
          position(props.clientRect)
        },
        onKeyDown: (props: SuggestionKeyDownProps) => {
          if (currentItems.length === 0) return false
          if (props.event.key === 'ArrowDown') {
            selectedIndex = (selectedIndex + 1) % currentItems.length
            renderItems()
            return true
          }
          if (props.event.key === 'ArrowUp') {
            selectedIndex = (selectedIndex - 1 + currentItems.length) % currentItems.length
            renderItems()
            return true
          }
          if (props.event.key === 'Enter') {
            select(selectedIndex)
            return true
          }
          if (props.event.key === 'Escape') {
            popup?.remove()
            popup = null
            return true
          }
          return false
        },
        onExit: () => {
          popup?.remove()
          popup = null
        },
      }
    },
  }
}

// ── Editor ────────────────────────────────────────────────────────────────────

interface RichTextEditorProps {
  content?: string
  onChange?: (html: string) => void
  onBlur?: (html: string) => void
  placeholder?: string
  className?: string
  minHeight?: string
  autoFocus?: boolean
  mentionable?: MentionableUser[]
  /** When set, images pasted/dropped into the editor are also registered as
   * attachments on this issue, so they show up in the issue's attachment gallery. */
  issueId?: number
  onAttachmentUploaded?: (attachment: UploadedAttachment) => void
}

export function RichTextEditor({
  content = '',
  onChange,
  onBlur,
  placeholder,
  className,
  minHeight = 'min-h-[120px]',
  autoFocus = false,
  mentionable,
  issueId,
  onAttachmentUploaded,
}: RichTextEditorProps) {
  const { t } = useI18n()
  const mentionNoMatchesLabel = t('planner_editor_no_mentions')
  const fileInputRef = useRef<HTMLInputElement>(null)

  const extensions = useMemo(() => {
    const base: AnyExtension[] = [
      StarterKit,
      InteractiveImage.configure({ inline: true, allowBase64: false }),
    ]
    if (mentionable?.length) {
      base.push(
        Mention.configure({
          HTMLAttributes: { class: 'text-primary font-medium' },
          suggestion: createMentionSuggestion(mentionable, mentionNoMatchesLabel),
        }),
      )
    }
    return base
  }, [mentionable, mentionNoMatchesLabel])

  const editor = useEditor({
    extensions,
    content,
    autofocus: autoFocus,
    editorProps: {
      attributes: {
        class: cn(
          'prose prose-sm dark:prose-invert max-w-none p-3 focus:outline-none',
          minHeight,
          placeholder &&
            'empty:before:content-[attr(data-placeholder)] before:text-muted-foreground before:pointer-events-none before:float-left before:h-0',
        ),
        'data-placeholder': placeholder ?? '',
      },
    },
    onUpdate: ({ editor }) => onChange?.(editor.getHTML()),
    onBlur: ({ editor }) => onBlur?.(editor.getHTML()),
  })

  const uploadImage = useCallback(
    async (file: File) => {
      if (!editor) return
      if (undefined === issueId) {
        toast.error(t('planner_editor_save_issue_before_image_upload'))
        return
      }

      try {
        const result = await uploadsApi.image(issueId, file)
        editor.chain().focus().setImage({ src: result.url }).run()
        if (result.attachment) onAttachmentUploaded?.(result.attachment)
      } catch {
        toast.error(t('planner_editor_image_upload_error'))
      }
    },
    [editor, issueId, onAttachmentUploaded, t],
  )

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) uploadImage(file)
    e.target.value = ''
  }

  const handlePaste = useCallback(
    (e: React.ClipboardEvent) => {
      const items = Array.from(e.clipboardData.items)
      const imageItem = items.find((i) => i.type.startsWith('image/'))
      if (imageItem) {
        e.preventDefault()
        const file = imageItem.getAsFile()
        if (file) uploadImage(file)
      }
    },
    [uploadImage],
  )

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      const file = e.dataTransfer.files[0]
      if (file?.type.startsWith('image/')) {
        e.preventDefault()
        uploadImage(file)
      }
    },
    [uploadImage],
  )

  if (!editor) return null

  return (
    <div
      className={cn('rounded-md border focus-within:ring-1 focus-within:ring-ring', className)}
      onPaste={handlePaste}
      onDrop={handleDrop}
      onDragOver={(e) => e.preventDefault()}
    >
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-0.5 border-b px-2 py-1">
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBold().run()}
          active={editor.isActive('bold')}
          title={t('planner_editor_bold')}
        >
          <IconBold className="h-3.5 w-3.5" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleItalic().run()}
          active={editor.isActive('italic')}
          title={t('planner_editor_italic')}
        >
          <IconItalic className="h-3.5 w-3.5" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleStrike().run()}
          active={editor.isActive('strike')}
          title={t('planner_editor_strikethrough')}
        >
          <IconStrikethrough className="h-3.5 w-3.5" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleCode().run()}
          active={editor.isActive('code')}
          title={t('planner_editor_code')}
        >
          <IconCode className="h-3.5 w-3.5" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-4" />

        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          active={editor.isActive('bulletList')}
          title={t('planner_editor_bullet_list')}
        >
          <IconList className="h-3.5 w-3.5" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          active={editor.isActive('orderedList')}
          title={t('planner_editor_numbered_list')}
        >
          <IconListNumbers className="h-3.5 w-3.5" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
          active={editor.isActive('blockquote')}
          title={t('planner_editor_quote')}
        >
          <IconBlockquote className="h-3.5 w-3.5" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-4" />

        <ToolbarButton
          onClick={() => fileInputRef.current?.click()}
          title={t('planner_editor_insert_image')}
          disabled={undefined === issueId}
        >
          <IconPhoto className="h-3.5 w-3.5" />
        </ToolbarButton>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={handleFileChange}
        />

        <span className="ml-auto text-[10px] text-muted-foreground hidden sm:block">
          {t('planner_editor_paste_or_drop')}
        </span>
      </div>

      <EditorContent editor={editor} />
    </div>
  )
}

function ToolbarButton({
  onClick,
  active,
  title,
  disabled = false,
  children,
}: {
  onClick: () => void
  active?: boolean
  title: string
  disabled?: boolean
  children: React.ReactNode
}) {
  return (
    <button
      type="button"
      disabled={disabled}
      onMouseDown={(e) => {
        e.preventDefault()
        onClick()
      }}
      title={title}
      className={cn(
        'rounded p-1 transition-colors hover:bg-muted',
        active && 'bg-muted text-foreground',
        !active && 'text-muted-foreground',
        disabled && 'cursor-not-allowed opacity-50',
      )}
    >
      {children}
    </button>
  )
}

export function getRichTextContent(editorRef: { getHTML: () => string } | null): string {
  return editorRef?.getHTML() ?? ''
}

export function getMentionedUserIds(html: string): number[] {
  const doc = new DOMParser().parseFromString(html, 'text/html')
  const ids = Array.from(doc.querySelectorAll('[data-type="mention"]'))
    .map((el) => Number(el.getAttribute('data-id')))
    .filter((id) => Number.isFinite(id))
  return Array.from(new Set(ids))
}
