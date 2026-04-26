"use client";

import { useState } from "react";
import { apiRequest } from "@/lib/api";
import { AuthUser, saveAuth, getToken } from "@/lib/auth";
import { useAutoAnimate } from "@formkit/auto-animate/react";

interface Props {
  user: AuthUser;
  isAdmin?: boolean;
}

export default function GeneralTab({ user, isAdmin }: Props) {
  const [name, setName] = useState(user.name);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [parent] = useAutoAnimate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");
    setError("");

    const { data, error: apiError } = await apiRequest<{ message: string, user: AuthUser }>("/profile", {
      method: "PATCH",
      body: { name },
    });

    setLoading(false);

    if (apiError) {
      setError(apiError);
    } else {
      setMessage("Profile updated successfully");
      
      // Update local storage and cookie
      const token = getToken();
      if (token && data?.user) {
        saveAuth(token, data.user);
      }
      
      // Hide message after 3 seconds
      setTimeout(() => setMessage(""), 3000);
    }
  };

  return (
    <div className={`${isAdmin ? 'bg-white border-zinc-200 shadow-sm' : 'bg-zinc-900/50 border-zinc-800/50 shadow-xl'} border p-6 sm:p-8 rounded-2xl backdrop-blur-sm transition-all duration-300`}>
      <div className="flex items-center gap-3 mb-6">
        <div className={`p-2 rounded-lg ${isAdmin ? 'bg-blue-50' : 'bg-orange-500/10'}`}>
          <svg className={`w-6 h-6 ${isAdmin ? 'text-blue-600' : 'text-orange-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <div>
          <h2 className={`text-xl font-bold ${isAdmin ? 'text-zinc-900' : 'text-white'}`}>General Information</h2>
          <p className={`text-sm ${isAdmin ? 'text-zinc-500' : 'text-zinc-400'}`}>Update your personal details here.</p>
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
          <label className={`block text-sm font-medium mb-2 transition-colors ${isAdmin ? 'text-zinc-700 group-focus-within:text-blue-600' : 'text-zinc-300 group-focus-within:text-orange-500'}`}>
            Email Address
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <input
              type="email"
              value={user.email}
              disabled
              className={`w-full pl-10 pr-4 py-3 border rounded-xl cursor-not-allowed outline-none ${isAdmin ? 'bg-zinc-100 border-zinc-200 text-zinc-500' : 'bg-zinc-800/50 border-zinc-700/50 text-zinc-500'}`}
            />
          </div>
          <p className="mt-2 text-xs text-zinc-500">Your email address cannot be changed at this time.</p>
        </div>
        
        <div className="group">
          <label className={`block text-sm font-medium mb-2 transition-colors ${isAdmin ? 'text-zinc-700 group-focus-within:text-blue-600' : 'text-zinc-300 group-focus-within:text-orange-500'}`}>
            Full Name
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className={`h-5 w-5 transition-colors ${isAdmin ? 'text-zinc-400 group-focus-within:text-blue-600' : 'text-zinc-500 group-focus-within:text-orange-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              className={`w-full pl-10 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 ${isAdmin ? 'bg-white border-zinc-300 text-zinc-900 placeholder-zinc-400 focus:ring-blue-500/50 focus:border-blue-500' : 'bg-zinc-900 border-zinc-700 text-white placeholder-zinc-500 focus:ring-orange-500/50 focus:border-orange-500'}`}
              placeholder="e.g. John Doe"
            />
          </div>
        </div>

        <div className={`pt-4 border-t flex justify-end ${isAdmin ? 'border-zinc-200' : 'border-zinc-800/50'}`}>
          <button
            type="submit"
            disabled={loading || name === user.name || !name.trim()}
            className={`relative px-6 py-3 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 overflow-hidden group ${isAdmin ? 'bg-blue-600 hover:bg-blue-700 focus:ring-offset-white focus:ring-blue-600' : 'bg-orange-500 hover:bg-orange-600 focus:ring-offset-zinc-900 focus:ring-orange-500'}`}
          >
            <span className={`flex items-center gap-2 ${loading ? 'opacity-0' : 'opacity-100'}`}>
              Save Changes
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
