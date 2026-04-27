'use client';

import { Activity, ShieldCheck, LifeBuoy } from 'lucide-react';

export default function AdminFooter() {
  return (
    <footer className="adm-footer">
      <div className="adm-footer-inner">
        
        {/* Left: Version & Copyright */}
        <div className="adm-footer-left">
          <span className="adm-version">v2.4.5</span>
          <span className="adm-copyright">© 2026 KitchenFlow Management</span>
        </div>

        {/* Center: System Status */}
        <div className="adm-footer-center">
          <div className="adm-status-chip">
            <Activity size={14} className="text-emerald-500" />
            <span>Server: <strong className="text-emerald-500">Stable</strong></span>
          </div>
          <div className="adm-status-divider" />
          <div className="adm-status-chip">
            <ShieldCheck size={14} className="text-emerald-500" />
            <span>Database: <strong className="text-emerald-500">Synced</strong></span>
          </div>
        </div>

        {/* Right: Support Links */}
        <div className="adm-footer-right">
          <button className="adm-footer-link">
            <LifeBuoy size={14} />
            Help Center
          </button>
          <div className="adm-status-divider" />
          <button className="adm-footer-link">API Status</button>
        </div>

      </div>
    </footer>
  );
}
