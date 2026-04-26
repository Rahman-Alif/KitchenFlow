"use client";

import { useState } from "react";
import { apiRequest } from "@/lib/api";
import { useAutoAnimate } from "@formkit/auto-animate/react";

export default function SecurityTab({ isAdmin }: { isAdmin?: boolean }) {
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [parent] = useAutoAnimate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== passwordConfirmation) {
      setError("New passwords do not match.");
      return;
    }

    setLoading(true);
    setMessage("");
    setError("");

    const { error: apiError } = await apiRequest("/profile/password", {
      method: "PUT",
      body: {
        current_password: currentPassword,
        password: password,
        password_confirmation: passwordConfirmation,
      },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
    } else {
      setMessage("Password updated successfully.");
      setCurrentPassword("");
      setPassword("");
      setPasswordConfirmation("");
      setTimeout(() => setMessage(""), 3000);
    }
  };

  return (
    <div className="bg-white border-slate-200 shadow-sm border p-6 sm:p-8 rounded-2xl backdrop-blur-sm transition-all duration-300">
      <div className="flex items-center gap-3 mb-6">
        <div className="p-2 rounded-lg bg-rose-50">
          <svg className="w-6 h-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div>
          <h2 className="text-xl font-bold text-slate-900">Security Settings</h2>
          <p className="text-sm text-slate-500">Update your password to keep your account secure.</p>
        </div>
      </div>
      
      <div ref={parent}>
        {message && (
          <div className="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm flex items-center gap-2">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
            {message}
          </div>
        )}
        {error && (
          <div className="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl text-sm flex items-center gap-2">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {error}
          </div>
        )}
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="group">
          <label className="block text-sm font-medium mb-2 transition-colors text-slate-700 group-focus-within:text-rose-600">
            Current Password
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 transition-colors text-slate-400 group-focus-within:text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
            </div>
            <input
              type="password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              required
              className="w-full pl-10 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 bg-white border-slate-300 text-slate-900 placeholder-slate-400 focus:ring-rose-500/50 focus:border-rose-500"
              placeholder="••••••••"
            />
          </div>
        </div>
        
        <div className="group">
          <label className="block text-sm font-medium mb-2 transition-colors text-slate-700 group-focus-within:text-rose-600">
            New Password
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 transition-colors text-slate-400 group-focus-within:text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              className="w-full pl-10 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 bg-white border-slate-300 text-slate-900 placeholder-slate-400 focus:ring-rose-500/50 focus:border-rose-500"
              placeholder="Minimum 8 characters"
            />
          </div>
        </div>

        <div className="group">
          <label className="block text-sm font-medium mb-2 transition-colors text-slate-700 group-focus-within:text-rose-600">
            Confirm New Password
          </label>
          <div className="relative">
             <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 transition-colors text-slate-400 group-focus-within:text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <input
              type="password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              minLength={8}
              className="w-full pl-10 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 bg-white border-slate-300 text-slate-900 placeholder-slate-400 focus:ring-rose-500/50 focus:border-rose-500"
              placeholder="Must match new password"
            />
          </div>
        </div>

        <div className="pt-4 border-t border-slate-200 flex justify-end">
          <button
            type="submit"
            disabled={loading || !currentPassword || !password || !passwordConfirmation}
            className="relative px-6 py-3 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 overflow-hidden group bg-rose-600 hover:bg-rose-700 focus:ring-offset-white focus:ring-rose-600"
          >
            <span className={`flex items-center gap-2 ${loading ? 'opacity-0' : 'opacity-100'}`}>
              Update Password
              <svg className="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            </span>
            {loading && (
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              </div>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
