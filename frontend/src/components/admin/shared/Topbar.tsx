'use client';

import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { apiRequest } from '@/lib/api';
import { clearAuth, getUser } from '@/lib/auth';

interface TopbarProps {
  title: string;
}

interface TenantData {
  name:                 string;
  subscription_active:  boolean;
  subscription_ends_at: string;
}

interface TenantResponse {
  data: TenantData;
}

export default function Topbar({ title }: TopbarProps) {
  const router = useRouter();

  const [logoutLoading, setLogoutLoading] = useState(false);
  const [dropdownOpen,  setDropdownOpen]  = useState(false);
  const [tenant,        setTenant]        = useState<TenantData | null>(null);
  const [tenantError,   setTenantError]   = useState<string | null>(null);
  const [tenantLoading, setTenantLoading] = useState(false);
  const [userName,      setUserName]      = useState<string>('Admin');

  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const u = getUser();
    if (u) setUserName(u.name ?? u.email ?? 'Admin');
  }, []);
  // Close when clicking outside
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setDropdownOpen(false);
      }
    }
    if (dropdownOpen) document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [dropdownOpen]);

  async function handleSubscriptionClick() {
    if (dropdownOpen) {
      setDropdownOpen(false);
      return;
    }

    setDropdownOpen(true);

    // Only fetch once
    if (tenant) return;

    setTenantLoading(true);
    const { data, error } = await apiRequest<TenantResponse>('/admin/tenant');
    setTenantLoading(false);

    if (error || !data) {
      setTenantError(error ?? 'Failed to load.');
    } else {
      setTenant(data.data);
    }
  }

  async function handleLogout() {
    if (logoutLoading) return;
    setLogoutLoading(true);
    await apiRequest('/auth/logout', { method: 'POST' });
    clearAuth();
    router.push('/login');
  }

  return (
    <header className="adm-topbar">
      <h1>{title}</h1>

      <div className="adm-user-area">
        <span>{userName}</span>

        {/* Subscription button + dropdown */}
        <div className="adm-subscription-wrapper" ref={dropdownRef}>
          <button
            type="button"
            className={`adm-subscription-btn${dropdownOpen ? ' adm-subscription-btn--active' : ''}`}
            onClick={handleSubscriptionClick}
          >
            Subscription
          </button>

          {dropdownOpen && (
            <div className="adm-subscription-dropdown">
              <div className="adm-subscription-dropdown-title">Subscription Details</div>

              {tenantLoading && (
                <p className="adm-subscription-muted">Loading…</p>
              )}

              {tenantError && (
                <p className="adm-subscription-error">{tenantError}</p>
              )}

              {tenant && (
                <>
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Organisation</span>
                    <span className="adm-subscription-value">{tenant.name}</span>
                  </div>
                  <div className="adm-subscription-divider" />
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Status</span>
                    <span className={`adm-subscription-badge ${tenant.subscription_active ? 'adm-subscription-badge--active' : 'adm-subscription-badge--inactive'}`}>
                      {tenant.subscription_active ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                  <div className="adm-subscription-divider" />
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Deadline</span>
                    <span className="adm-subscription-value">
                      {new Date(tenant.subscription_ends_at).toLocaleDateString('en-GB', {
                        day:   'numeric',
                        month: 'long',
                        year:  'numeric',
                      })}
                    </span>
                  </div>
                </>
              )}
            </div>
          )}
        </div>

        <button
          type="button"
          className="adm-logout-btn"
          onClick={handleLogout}
          disabled={logoutLoading}
        >
          {logoutLoading ? 'Signing out...' : 'Logout'}
        </button>
      </div>
    </header>
  );
}