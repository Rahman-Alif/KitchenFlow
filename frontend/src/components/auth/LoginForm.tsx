'use client';

// frontend/src/components/auth/LoginForm.tsx

import { useState, useEffect, useRef } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { apiRequest } from '@/lib/api';
import { saveAuth, getHomeRoute, AuthUser } from '@/lib/auth';

interface LoginResponse {
  data: {
    token: string;
    user:  AuthUser;
  };
}

export default function LoginForm() {
  const router       = useRouter();
  const searchParams = useSearchParams();

  const [email,    setEmail]    = useState('');
  const [password, setPassword] = useState('');
  const [error,    setError]    = useState<string | null>(null);
  const [loading,  setLoading]  = useState(false);

  // Toast state
  const [toastVisible, setToastVisible] = useState(false);
  const [toastHiding,  setToastHiding]  = useState(false);
  const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  function showToast() {
    setToastVisible(true);
    setToastHiding(false);

    // After 4s start the slide-out, then unmount after 400ms (matches CSS transition)
    hideTimerRef.current = setTimeout(() => {
      setToastHiding(true);
      setTimeout(() => setToastVisible(false), 400);
    }, 7000);
  }

  useEffect(() => {
    if (searchParams.get('reset') === 'success') showToast();
    return () => {
      if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
    };
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const { data, error: apiError } = await apiRequest<LoginResponse>('/auth/login', {
      method: 'POST',
      body:   { email, password },
    });

    setLoading(false);

    if (apiError || !data) {
      setError(apiError ?? 'Login failed. Please try again.');
      return;
    }

    saveAuth(data.data.token, data.data.user);
    router.push(getHomeRoute(data.data.user.role));
  }

  // Build toast class string
  const toastClass = [
    'auth-toast',
    toastVisible  ? 'auth-toast--visible' : '',
    toastHiding   ? 'auth-toast--hiding'  : '',
  ].join(' ').trim();

  return (
    <div className="auth-page-wrapper">
      <div className="auth-card">

        {/* Brand */}
        <div className="auth-brand-area">
          <div className="auth-logo">KitchenFlow</div>
          <div className="auth-heading">Welcome back</div>
        </div>

        {/* Error */}
        {error && (
          <div className="auth-error-msg" style={{ marginBottom: '16px' }}>
            {error}
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} noValidate>

          <div className="auth-form-group">
            <label className="auth-label" htmlFor="email">Email</label>
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

          <div className="auth-form-group">
            <label className="auth-label" htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              className="auth-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              autoComplete="current-password"
            />
          </div>

          <div className="auth-flex-row">
            <Link href="/reset-password/request" className="auth-link">
              Forgot password?
            </Link>
          </div>

          <button type="submit" className="auth-btn" disabled={loading}>
            {loading ? (
              <>
                <span className="auth-loader" />
                Signing in…
              </>
            ) : (
              'Sign in'
            )}
          </button>

        </form>
      </div>

      {/* Toast — rendered outside the card so it sits at viewport bottom */}
      {toastVisible && (
        <div className={toastClass}>
          <div className="auth-toast-icon">✓</div>
          <span className="auth-toast-text">
            Password updated successfully. Please sign in.
          </span>
        </div>
      )}
    </div>
  );
}