'use client';

interface AdminConfirmDialogProps {
  open: boolean;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  danger?: boolean;
  inputLabel?: string;
  inputValue?: string;
  inputType?: 'text' | 'number';
  inputMin?: number;
  onInputChange?: (value: string) => void;
  onConfirm: () => void;
  onCancel: () => void;
}

export default function AdminConfirmDialog({
  open,
  title,
  message,
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  danger = false,
  inputLabel,
  inputValue = '',
  inputType = 'text',
  inputMin,
  onInputChange,
  onConfirm,
  onCancel,
}: AdminConfirmDialogProps) {
  if (!open) return null;

  return (
    <div className="adm-modal-backdrop" role="dialog" aria-modal="true" aria-label={title}>
      <div className="adm-modal-card">
        <h3 className="adm-modal-title">{title}</h3>
        <p className="adm-modal-message">{message}</p>

        {onInputChange && (
          <label className="adm-modal-input-wrap">
            <span>{inputLabel ?? 'Value'}</span>
            <input
              type={inputType}
              min={inputMin}
              value={inputValue}
              onChange={(event) => onInputChange(event.target.value)}
              className="adm-modal-input"
            />
          </label>
        )}

        <div className="adm-modal-actions">
          <button type="button" className="adm-modal-btn" onClick={onCancel}>
            {cancelText}
          </button>
          <button
            type="button"
            className={danger ? 'adm-modal-btn adm-modal-btn--danger' : 'adm-modal-btn adm-modal-btn--primary'}
            onClick={onConfirm}
          >
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}
