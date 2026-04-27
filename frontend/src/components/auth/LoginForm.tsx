'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { Mail, Lock, Eye, EyeOff, Zap, AlertCircle, CheckCircle2 } from 'lucide-react';
import { apiRequest } from '@/lib/api';
import { saveAuth, getHomeRoute, AuthUser } from '@/lib/auth';

interface LoginResponse {
  data: {
    token: string;
    user: AuthUser;
  };
}

export default function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [greeting, setGreeting] = useState('Welcome back');

  // Toast state
  const [toastVisible, setToastVisible] = useState(false);
  const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    // Dynamic greeting based on time
    const hour = new Date().getHours();
    if (hour < 12) setGreeting('Good Morning');
    else if (hour < 18) setGreeting('Good Afternoon');
    else setGreeting('Good Evening');

    if (searchParams.get('reset') === 'success') {
      setToastVisible(true);
      hideTimerRef.current = setTimeout(() => setToastVisible(false), 5000);
    }

    return () => {
      if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
    };
  }, [searchParams]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    const { data, error: apiError } = await apiRequest<LoginResponse>('/auth/login', {
      method: 'POST',
      body: { email, password },
    });

    setLoading(false);

    if (apiError || !data) {
      setError(apiError ?? 'Login failed. Please check your credentials.');
      return;
    }

    saveAuth(data.data.token, data.data.user);
    router.push(getHomeRoute(data.data.user.role));
  }

  return (
    <div className="auth-page-wrapper">
      {/* Right Side: Login Form */}
      <div className="auth-side-form">
        <div className="auth-card">

          {/* Brand Logo */}
          <div className="flex items-center gap-3 mb-8 justify-center lg:justify-start">
            <div className="bg-orange-500 p-2 rounded-xl shadow-lg shadow-orange-500/20">
              <Zap className="w-6 h-6 text-white fill-white" />
            </div>
            <span className="text-2xl font-black tracking-tighter text-slate-800">
              Kitchen<span className="text-orange-500">Flow</span>
            </span>
          </div>

          <div className="auth-brand-area text-center lg:text-left">
            <span className="auth-greeting">{greeting}</span>
            <h2 className="auth-heading">Sign in to workspace</h2>
          </div>

          {error && (
            <div className="auth-error-msg">
              <AlertCircle className="w-5 h-5" />
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} noValidate className="space-y-6">

            <div className="auth-form-group">
              <label className="auth-label" htmlFor="email">Email Address</label>
              <div className="auth-input-container">
                <Mail className="auth-input-icon" />
                <input
                  id="email"
                  type="email"
                  className="auth-input"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="name@example.com"
                  required
                  autoComplete="email"
                />
              </div>
            </div>

            <div className="auth-form-group">
              <div className="flex justify-between items-center mb-2">
                <label className="auth-label mb-0" htmlFor="password">Password</label>
                <Link href="/reset-password/request" className="auth-link text-xs">
                  Forgot password?
                </Link>
              </div>
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
                  autoComplete="current-password"
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

            <button type="submit" className="auth-btn" disabled={loading}>
              {loading ? (
                <div className="flex items-center justify-center gap-2">
                  <div className="auth-loader" />
                  <span>Signing in...</span>
                </div>
              ) : (
                'Sign in'
              )}
            </button>
          </form>

          <p className="mt-8 text-center text-sm text-slate-500">
            Don't have an account? <span className="text-orange-500 font-semibold cursor-help">Contact Admin</span>
          </p>
        </div>
      </div>

      {/* Toast Notification */}
      <div className={`auth-toast ${toastVisible ? 'auth-toast--visible' : ''}`}>
        <CheckCircle2 className="w-6 h-6 text-emerald-500" />
        <span className="auth-toast-text">Password updated successfully!</span>
      </div>
    </div>
  );
}