'use client';

// frontend/src/components/auth/PasswordResetRequestForm.tsx
// Sends the reset email. On success, replaces the form with a confirmation message.

import { useState } from 'react';
import Link from 'next/link';
import { apiRequest } from '@/lib/api';

export default function PasswordResetRequestForm() {
  const [email,   setEmail]   = useState('');
  const [error,   setError]   = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [sent,    setSent]    = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const { error: apiError } = await apiRequest('/auth/password-reset/request', {
      method: 'POST',
      body:   { email },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
      return;
    }

    setSent(true);
  }

  return (
    <div className="auth-page-wrapper">
      <div className="auth-card">

        <div className="auth-brand-area">
          <div className="auth-logo">KitchenFlow</div>
          <div className="auth-heading">Reset your password</div>
        </div>

        {/* Success state — replaces the form */}
        {sent ? (
          <div className="auth-success-box">
            <div className="auth-success-icon">✓</div>
            <p className="auth-heading" style={{ marginBottom: '8px' }}>Check your inbox</p>
            <p className="auth-subtext" style={{ marginBottom: 0 }}>
              We sent a password reset link to <strong>{email}</strong>.
              Use the token in that email on the next step.
            </p>
            <Link href="/reset-password/confirm" className="auth-back-link">
              Enter reset token →
            </Link>
          </div>
        ) : (
          <>
            <p className="auth-subtext">
              Enter your work email and we'll send you a reset link.
            </p>

            {error && (
              <div className="auth-error-msg" style={{ marginBottom: '16px' }}>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} noValidate>
              <div className="auth-form-group">
                <label className="auth-label" htmlFor="email">
                  Email
                </label>
                <input
                  id="email"
                  type="email"
                  className="auth-input"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@company.com"
                  required
                  autoComplete="email"
                />
              </div>

              <button type="submit" className="auth-btn" disabled={loading}>
                {loading ? (
                  <>
                    <span className="auth-loader" />
                    Sending…
                  </>
                ) : (
                  'Send reset link'
                )}
              </button>
            </form>

            <Link href="/login" className="auth-back-link">
              ← Back to login
            </Link>
          </>
        )}

      </div>
    </div>
  );
}