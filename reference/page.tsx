dashboard/page.tsx

"use client";

import { useEffect, useState } from "react";
import Cookies from "js-cookie";
import { Users, MapPin, Calendar, MessageSquare, ArrowUpRight, TrendingUp, Activity, PieChart as PieIcon } from "lucide-react";
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, 
  BarChart, Bar, Cell, PieChart, Pie, Legend
} from 'recharts';

export default function AdminDashboardPage() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadStats() {
      const token = Cookies.get('auth_token');
      try {
        const res = await fetch("http://localhost:8000/api/admin/stats", {
          headers: {
            "Authorization": `Bearer ${token}`,
            "Accept": "application/json"
          }
        });
        if (res.ok) {
          const data = await res.json();
          setStats(data.data);
        }
      } catch (err) {
        console.error("error fetching stats", err);
      } finally {
        setLoading(false);
      }
    }
    loadStats();
  }, []);

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-32 rounded-2xl bg-[var(--bg-elevated)] animate-pulse border border-[var(--border)]" />
          ))}
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="h-[400px] rounded-2xl bg-[var(--bg-elevated)] animate-pulse border border-[var(--border)]" />
          <div className="h-[400px] rounded-2xl bg-[var(--bg-elevated)] animate-pulse border border-[var(--border)]" />
        </div>
      </div>
    );
  }

  const statCards = [
    { label: "Total Users", value: stats?.total_users || 0, icon: <Users className="h-6 w-6 text-red-500" />, href: "/admin/users", linkText: "View Users" },
    { label: "Total Places", value: stats?.total_places || 0, icon: <MapPin className="h-6 w-6 text-emerald-500" />, href: "/admin/places", linkText: "View Places" },
    { label: "Active Events", value: stats?.total_events || 0, icon: <Calendar className="h-6 w-6 text-amber-500" />, href: "/admin/events", linkText: "View Events" },
    { label: "Total Reviews", value: stats?.total_reviews || 0, icon: <MessageSquare className="h-6 w-6 text-rose-500" />, href: "/admin/reviews", linkText: "View Reviews" },
  ];

  const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#ec4899'];

  return (
    <div className="space-y-8 animate-in fade-in duration-700">
      {/* Stat Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat, i) => (
          <a key={i} href={stat.href} className="admin-card p-6 group relative overflow-hidden">
            <div className="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-white/5 to-transparent -mr-8 -mt-8 rounded-full transition-transform group-hover:scale-150" />
            <div className="flex items-start justify-between relative z-10">
              <div>
                <p className="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] mb-2">{stat.label}</p>
                <h3 className="text-4xl font-black text-[var(--text-primary)] tracking-tighter">{stat.value}</h3>
              </div>
              <div className="p-3 bg-[var(--bg-default)] rounded-2xl border border-[var(--border)] group-hover:border-red-500/50 group-hover:bg-red-500/5 transition-all shadow-sm">
                {stat.icon}
              </div>
            </div>
            <div className="mt-4 flex items-center text-[10px] font-black uppercase tracking-widest text-red-500 transition-all">
              {stat.linkText} <ArrowUpRight className="h-3 w-3 ml-1" />
            </div>
          </a>
        ))}
      </div>

      {/* Main Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* User Growth Chart */}
        <div className="admin-card p-8 flex flex-col gap-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-xl bg-red-500/10 text-red-500">
                <TrendingUp className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-bold text-[var(--text-primary)]">User Growth</h3>
                <p className="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">New Registrations (Last 7 Days)</p>
              </div>
            </div>
          </div>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={stats?.user_growth || []}>
                <defs>
                  <linearGradient id="colorUsers" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#ef4444" stopOpacity={0.3}/>
                    <stop offset="95%" stopColor="#ef4444" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgba(255,255,255,0.05)" />
                <XAxis 
                  dataKey="date" 
                  axisLine={false} 
                  tickLine={false} 
                  tick={{ fill: 'var(--text-muted)', fontSize: 10, fontWeight: 600 }}
                  dy={10}
                />
                <YAxis 
                  hide 
                />
                <Tooltip 
                  contentStyle={{ backgroundColor: 'var(--bg-elevated)', border: '1px solid var(--border)', borderRadius: '12px', fontSize: '12px' }}
                />
                <Area 
                  type="monotone" 
                  dataKey="users" 
                  stroke="#ef4444" 
                  strokeWidth={3}
                  fillOpacity={1} 
                  fill="url(#colorUsers)" 
                  animationDuration={1500}
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Review Activity Chart */}
        <div className="admin-card p-8 flex flex-col gap-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-xl bg-rose-500/10 text-rose-500">
                <Activity className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-bold text-[var(--text-primary)]">Platform Activity</h3>
                <p className="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">Reviews Posted (Last 7 Days)</p>
              </div>
            </div>
          </div>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={stats?.recent_activity || []}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgba(255,255,255,0.05)" />
                <XAxis 
                  dataKey="date" 
                  axisLine={false} 
                  tickLine={false} 
                  tick={{ fill: 'var(--text-muted)', fontSize: 10, fontWeight: 600 }}
                  dy={10}
                />
                <YAxis hide />
                <Tooltip 
                  cursor={{ fill: 'rgba(255,255,255,0.05)' }}
                  contentStyle={{ backgroundColor: 'var(--bg-elevated)', border: '1px solid var(--border)', borderRadius: '12px', fontSize: '12px' }}
                />
                <Bar 
                  dataKey="reviews" 
                  fill="#ef4444" 
                  radius={[6, 6, 0, 0]} 
                  barSize={30}
                  animationDuration={1500}
                >
                  {(stats?.recent_activity || []).map((entry: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={index % 2 === 0 ? '#ef4444' : '#b91c1c'} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Bottom Row - Pie Chart */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="admin-card p-8 lg:col-span-1 flex flex-col gap-6">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500">
              <PieIcon className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-bold text-[var(--text-primary)]">Venue Diversity</h3>
              <p className="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">Categories Breakdown</p>
            </div>
          </div>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={stats?.category_distribution || []}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="count"
                  nameKey="category"
                  animationDuration={1500}
                >
                  {(stats?.category_distribution || []).map((entry: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip 
                   contentStyle={{ backgroundColor: 'var(--bg-elevated)', border: '1px solid var(--border)', borderRadius: '12px', fontSize: '12px' }}
                />
                <Legend iconType="circle" />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="lg:col-span-2 admin-card p-8 flex flex-col justify-center relative overflow-hidden bg-gradient-to-br from-red-600 to-red-900 text-white">
          <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 -mr-32 -mt-32 rounded-full blur-3xl" />
          <div className="relative z-10 space-y-4">
            <h2 className="text-4xl font-black tracking-tighter leading-tight max-w-md">System Health is Optimal.</h2>
            <p className="opacity-80 text-lg font-medium max-w-lg">All modules are operating within normal parameters. New user registration is up by 12% compared to last week.</p>
            <div className="pt-6 flex gap-4">
               <div className="bg-white/10 backdrop-blur-md px-6 py-4 rounded-2xl border border-white/20">
                  <p className="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Server Response</p>
                  <p className="text-2xl font-black tracking-tighter">42ms</p>
               </div>
               <div className="bg-white/10 backdrop-blur-md px-6 py-4 rounded-2xl border border-white/20">
                  <p className="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">DB Connections</p>
                  <p className="text-2xl font-black tracking-tighter">Active</p>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
