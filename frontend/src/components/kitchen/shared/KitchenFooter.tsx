'use client';

import { Flame, Timer, CheckCircle } from 'lucide-react';

export default function KitchenFooter() {
  return (
    <footer className="bg-slate-900 border-t border-slate-800 py-3 px-6 mt-auto">
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        
        {/* Left: Kitchen Status */}
        <div className="flex items-center gap-6">
          <div className="flex items-center gap-2 text-orange-400">
            <Flame size={16} className="animate-pulse" />
            <span className="text-xs font-bold uppercase tracking-wider">Kitchen Live</span>
          </div>
          <div className="hidden sm:flex items-center gap-2 text-slate-400 text-xs">
            <Timer size={14} />
            <span>Avg. Prep Time: <strong className="text-slate-200">12 min</strong></span>
          </div>
        </div>

        {/* Right: Quick Stats */}
        <div className="flex items-center gap-5">
           <div className="flex items-center gap-2 text-emerald-400">
            <CheckCircle size={16} />
            <span className="text-xs font-bold uppercase tracking-wider">Station Clear</span>
          </div>
          <div className="h-4 w-px bg-slate-800" />
          <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
            KitchenFlow OS
          </p>
        </div>

      </div>
    </footer>
  );
}
