'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import api from '@/lib/api';
import { setAuth, setAuthCookies } from '@/lib/auth';

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const res = await api.post('/auth/login', { email, password });
      console.log('Full response:', res.data);
      const { token, user } = res.data.data;
      console.log('Token:', token);
      console.log('User:', user);

      setAuth(token, user);
      setAuthCookies(token, user.role);

      console.log('Redirecting to role:', user.role);

      switch (user.role) {
        case 'kitchen_staff':
          router.push('/kitchen/orders');
          break;
        case 'user':
          router.push('/user/menu');
          break;
        case 'admin':
          router.push('/admin/dashboard');
          break;
        default:
          router.push('/login');
      }
    } catch (err: any) {
      console.log('Error:', err);
      const message =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.message ||
        'Login failed. Please try again.';
      setError(message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-950 flex items-center justify-center px-4">
      <div className="w-full max-w-sm">

        <div className="mb-10 text-center">
          <h1 className="text-3xl font-bold text-white tracking-tight">
            Kitchen<span className="text-orange-500">Flow</span>
          </h1>
          <p className="mt-2 text-zinc-400 text-sm">
            Sign in to your workspace
          </p>
        </div>

        <div className="bg-zinc-900 border border-zinc-800 rounded-2xl p-8">
          <form onSubmit={handleSubmit} className="space-y-5">

            {error && (
              <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3">
                {error}
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-zinc-300 mb-1.5">
                Email
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                placeholder="you@company.com"
                className="w-full bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-zinc-300 mb-1.5">
                Password
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="••••••••"
                className="w-full bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-orange-500/50 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition"
            >
              {loading ? 'Signing in...' : 'Sign in'}
            </button>

          </form>
        </div>

        <p className="mt-6 text-center text-zinc-600 text-xs">
          KitchenFlow by Betopia Limited
        </p>
      </div>
    </div>
  );
}