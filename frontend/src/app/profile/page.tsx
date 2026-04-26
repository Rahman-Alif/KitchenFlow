"use client";

import { useEffect, useState } from "react";
import { getUser, AuthUser, isAuthenticated } from "@/lib/auth";
import { useRouter } from "next/navigation";
import GeneralTab from "./components/GeneralTab";
import SecurityTab from "./components/SecurityTab";
import { apiRequest } from "@/lib/api";

import UserLayout from "@/components/user/layouts/UserLayout";
import KitchenLayout from "@/components/kitchen/layouts/KitchenLayout";
import AdminLayout from "@/components/admin/layouts/AdminLayout";
import "@/components/admin/layouts/admin-layout.css";
import { useAutoAnimate } from "@formkit/auto-animate/react";

type Tab = "general" | "security";

export default function ProfilePage() {
  const router = useRouter();
  const [user, setUser] = useState<AuthUser | null>(null);
  const [activeTab, setActiveTab] = useState<Tab>("general");
  const [loading, setLoading] = useState(true);
  const [parent] = useAutoAnimate();

  useEffect(() => {
    if (!isAuthenticated()) {
      router.push("/login");
      return;
    }

    // Load fresh user data
    apiRequest<{ user: AuthUser }>("/profile")
      .then((res) => {
        if (res.error || !res.data) {
          console.error("Failed to load profile", res.error);
          setUser(getUser());
        } else {
          setUser(res.data.user);
        }
      })
      .catch((err) => {
        console.error("Unexpected error", err);
        setUser(getUser());
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4">
        <div className="w-10 h-10 border-4 border-orange-500/20 border-t-orange-500 rounded-full animate-spin" />
        <p className="text-zinc-500 text-sm font-medium animate-pulse">Loading your profile...</p>
      </div>
    );
  }

  if (!user) return null;

  const isAdmin = user.role === "admin";

  const profileContent = (
    <div className={`max-w-5xl mx-auto p-4 md:p-8 ${isAdmin ? 'text-zinc-900' : 'text-white'}`}>
      <div className="mb-10 text-center md:text-left">
        <h1 className={`text-3xl md:text-4xl font-extrabold tracking-tight mb-2 ${isAdmin ? 'text-zinc-900' : 'text-white'}`}>Account Settings</h1>
        <p className={isAdmin ? 'text-zinc-500' : 'text-zinc-400'}>Manage your profile details and security preferences.</p>
      </div>

      <div className="flex flex-col md:flex-row gap-8 lg:gap-12">
        {/* Sidebar Navigation */}
        <div className="md:w-72 flex-shrink-0">
          <nav className="flex md:flex-col gap-2 overflow-x-auto pb-4 md:pb-0 scrollbar-hide">
            <button
              onClick={() => setActiveTab("general")}
              className={`flex items-center gap-3 px-5 py-4 text-sm font-medium rounded-2xl transition-all duration-300 w-full whitespace-nowrap md:whitespace-normal ${
                activeTab === "general"
                  ? isAdmin 
                    ? "bg-blue-50 text-blue-600 shadow-[inset_0_0_0_1px_rgba(37,99,235,0.2)]" 
                    : "bg-orange-500/10 text-orange-500 shadow-[inset_0_0_0_1px_rgba(249,115,22,0.2)]"
                  : isAdmin 
                    ? "text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900" 
                    : "text-zinc-400 hover:bg-zinc-800/50 hover:text-white"
              }`}
            >
              <svg className={`w-5 h-5 transition-colors ${activeTab === 'general' ? (isAdmin ? 'text-blue-600' : 'text-orange-500') : 'text-zinc-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              General Information
            </button>
            <button
              onClick={() => setActiveTab("security")}
              className={`flex items-center gap-3 px-5 py-4 text-sm font-medium rounded-2xl transition-all duration-300 w-full whitespace-nowrap md:whitespace-normal ${
                activeTab === "security"
                  ? isAdmin
                    ? "bg-rose-50 text-rose-600 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.2)]"
                    : "bg-rose-500/10 text-rose-500 shadow-[inset_0_0_0_1px_rgba(243,24,96,0.2)]"
                  : isAdmin
                    ? "text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
                    : "text-zinc-400 hover:bg-zinc-800/50 hover:text-white"
              }`}
            >
              <svg className={`w-5 h-5 transition-colors ${activeTab === 'security' ? (isAdmin ? 'text-rose-600' : 'text-rose-500') : 'text-zinc-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
              Security
            </button>
          </nav>
        </div>

        {/* Main Content Area */}
        <div className="flex-1 min-w-0" ref={parent}>
          {activeTab === "general" && <GeneralTab key="general" user={user} isAdmin={isAdmin} />}
          {activeTab === "security" && <SecurityTab key="security" isAdmin={isAdmin} />}
        </div>
      </div>
    </div>
  );

  if (user.role === "admin") {
    return <AdminLayout title="Profile">{profileContent}</AdminLayout>;
  }

  if (user.role === "kitchen_staff") {
    return <KitchenLayout>{profileContent}</KitchenLayout>;
  }

  return <UserLayout>{profileContent}</UserLayout>;
}
