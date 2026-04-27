'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Mail, Zap, AlertCircle, CheckCircle2, ChevronLeft } from 'lucide-react';
import { apiRequest } from '@/lib/api';

export default function PasswordResetRequestForm() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setMessage(null);
    setError(null);
    setLoading(true);

    const { error: apiError } = await apiRequest('/auth/password/email', {
      method: 'POST',
      body: { email },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
    } else {
      setMessage('Password reset link sent! Please check your inbox.');
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
          <h1 className="auth-quote">Don't worry, <br/>we've got you covered.</h1>
          <p className="auth-quote-sub">Reset your password and get back to managing your kitchen in no time.</p>
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
            <h2 className="auth-heading">Reset password</h2>
            <p className="text-slate-500 text-sm mt-2">Enter your email and we'll send you a reset link.</p>
          </div>

          {error && (
            <div className="auth-error-msg">
              <AlertCircle className="w-5 h-5" />
              {error}
            </div>
          )}

          {message && (
            <div className="bg-emerald-50 border border-emerald-100 text-emerald-600 p-4 rounded-xl text-sm font-medium mb-6 flex items-center gap-3">
              <CheckCircle2 className="w-5 h-5" />
              {message}
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
                />
              </div>
            </div>

            <button type="submit" className="auth-btn" disabled={loading}>
              {loading ? 'Sending link...' : 'Send reset link'}
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