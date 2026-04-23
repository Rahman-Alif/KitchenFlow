'use client';

// frontend/src/components/auth/PasswordResetConfirmForm.tsx
// Accepts token + new password. On success, redirects to login.

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { apiRequest } from '@/lib/api';

export default function PasswordResetConfirmForm() {
  const router = useRouter();

  const [token,    setToken]    = useState('');
  const [password, setPassword] = useState('');
  const [confirm,  setConfirm]  = useState('');
  const [error,    setError]    = useState<string | null>(null);
  const [loading,  setLoading]  = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);

    if (password !== confirm) {
      setError('Passwords do not match.');
      return;
    }

    setLoading(true);

    const { error: apiError } = await apiRequest('/auth/password-reset/confirm', {
      method: 'POST',
      body:   {
        token,
        password,
        password_confirmation: confirm,
      },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
      return;
    }

    // Small delay so the user sees success before redirect
    router.push('/login?reset=success');
  }

  return (
    <div className="auth-page-wrapper">
      <div className="auth-card">

        <div className="auth-brand-area">
          <div className="auth-logo">KitchenFlow</div>
          <div className="auth-heading">Set new password</div>
        </div>

        <p className="auth-subtext">
          Paste the token from your email and choose a new password.
        </p>

        {error && (
          <div className="auth-error-msg" style={{ marginBottom: '16px' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} noValidate>

          <div className="auth-form-group">
            <label className="auth-label" htmlFor="token">
              Reset token
            </label>
            <input
              id="token"
              type="text"
              className="auth-input"
              value={token}
              onChange={(e) => setToken(e.target.value)}
              placeholder="Paste token from email"
              required
              autoComplete="off"
            />
          </div>

          <div className="auth-form-group">
            <label className="auth-label" htmlFor="password">
              New password
            </label>
            <input
              id="password"
              type="password"
              className="auth-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              autoComplete="new-password"
            />
          </div>

          <div className="auth-form-group">
            <label className="auth-label" htmlFor="confirm">
              Confirm password
            </label>
            <input
              id="confirm"
              type="password"
              className="auth-input"
              value={confirm}
              onChange={(e) => setConfirm(e.target.value)}
              placeholder="••••••••"
              required
              autoComplete="new-password"
            />
          </div>

          <button type="submit" className="auth-btn" disabled={loading}>
            {loading ? (
              <>
                <span className="auth-loader" />
                Updating…
              </>
            ) : (
              'Set new password'
            )}
          </button>

        </form>

        <Link href="/login" className="auth-back-link">
          ← Back to login
        </Link>

      </div>
    </div>
  );
}