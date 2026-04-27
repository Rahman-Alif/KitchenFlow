'use client';

import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { Lock, Eye, EyeOff, Zap, AlertCircle, ChevronLeft } from 'lucide-react';
import { apiRequest } from '@/lib/api';

export default function PasswordResetConfirmForm() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const token = searchParams.get('token');
  const email = searchParams.get('email');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const { error: apiError } = await apiRequest('/auth/password/reset', {
      method: 'POST',
      body: {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
    } else {
      router.push('/login?reset=success');
    }
  }

  return (
    <div className="auth-page-wrapper">
      <div className="auth-side-image">
        <img
          src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1600&q=80"
          alt="Kitchen Scene"
        />
        <div className="auth-image-overlay">
          <h1 className="auth-quote">Security first, <br />flavor always.</h1>
          <p className="auth-quote-sub">Choose a strong password to keep your kitchen workspace safe.</p>
        </div>
      </div>

      <div className="auth-side-form">
        <div className="auth-card">
          <div className="flex items-center gap-3 mb-8 justify-center lg:justify-start">
            <div className="bg-orange-500 p-2 rounded-xl shadow-lg shadow-orange-500/20">
              <Zap className="w-6 h-6 text-white fill-white" />
            </div>
            <span className="text-2xl font-black tracking-tighter text-slate-800">
              Kitchen<span className="text-orange-500">Flow</span>
            </span>
          </div>

          <div className="auth-brand-area text-center lg:text-left">
            <h2 className="auth-heading">Set new password</h2>
            <p className="text-slate-500 text-sm mt-2">Almost there! Enter your new password below.</p>
          </div>

          {error && (
            <div className="auth-error-msg">
              <AlertCircle className="w-5 h-5" />
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} noValidate className="space-y-6">
            <div className="auth-form-group">
              <label className="auth-label" htmlFor="password">New Password</label>
              <div className="auth-input-container">
                <Lock className="auth-input-icon" />
                <input
                  id="password"
                  type={showPass ? 'text' : 'password'}
                  className="auth-input"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  required
                />
                <button
                  type="button"
                  className="auth-toggle-pass"
                  onClick={() => setShowPass(!showPass)}
                >
                  {showPass ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
            </div>

            <div className="auth-form-group">
              <label className="auth-label" htmlFor="password_confirmation">Confirm New Password</label>
              <div className="auth-input-container">
                <Lock className="auth-input-icon" />
                <input
                  id="password_confirmation"
                  type={showPass ? 'text' : 'password'}
                  className="auth-input"
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  placeholder="••••••••"
                  required
                />
              </div>
            </div>

            <button type="submit" className="auth-btn" disabled={loading}>
              {loading ? 'Updating...' : 'Update password'}
            </button>
          </form>

          <div className="mt-8 text-center">
            <Link href="/login" className="auth-link inline-flex items-center gap-2">
              <ChevronLeft className="w-4 h-4" />
              Back to sign in
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}





