'use client';

import Link from 'next/link';
import { Mail, Phone, Clock, Globe, MessageCircle, Share2 } from 'lucide-react';

export default function UserFooter() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="bg-white border-t border-slate-200 pt-12 pb-8 mt-auto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
          
          {/* Brand Column */}
          <div className="space-y-4">
            <Link href="/menu" className="flex items-center gap-2">
              <div className="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg shadow-orange-500/20">
                <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h2 className="text-xl font-black text-slate-900 tracking-tight">
                Kitchen<span className="text-orange-500">Flow</span>
              </h2>
            </Link>
            <p className="text-slate-500 text-sm leading-relaxed max-w-xs">
              Experience the art of fresh cooking. We bring the finest ingredients from the farm to your table with speed and passion.
            </p>
            <div className="flex items-center gap-4">
              <Link href="#" className="p-2 bg-slate-100 rounded-full text-slate-400 hover:text-orange-500 hover:bg-orange-50 transition-all">
                <Globe size={18} />
              </Link>
              <Link href="#" className="p-2 bg-slate-100 rounded-full text-slate-400 hover:text-orange-500 hover:bg-orange-50 transition-all">
                <MessageCircle size={18} />
              </Link>
              <Link href="#" className="p-2 bg-slate-100 rounded-full text-slate-400 hover:text-orange-500 hover:bg-orange-50 transition-all">
                <Share2 size={18} />
              </Link>
            </div>
          </div>

          {/* Quick Info Column */}
          <div className="space-y-5">
            <h3 className="text-sm font-bold text-slate-900 uppercase tracking-widest">Opening Hours</h3>
            <ul className="space-y-3">
              <li className="flex items-center gap-3 text-sm text-slate-500">
                <Clock size={16} className="text-orange-500" />
                <span>Mon - Fri: <strong className="text-slate-700">9:00 AM - 10:00 PM</strong></span>
              </li>
              <li className="flex items-center gap-3 text-sm text-slate-500">
                <Clock size={16} className="text-orange-500" />
                <span>Sat - Sun: <strong className="text-slate-700">11:00 AM - 11:00 PM</strong></span>
              </li>
            </ul>
          </div>

          {/* Contact Column */}
          <div className="space-y-5">
            <h3 className="text-sm font-bold text-slate-900 uppercase tracking-widest">Contact Us</h3>
            <ul className="space-y-3">
              <li>
                <a href="tel:+123456789" className="flex items-center gap-3 text-sm text-slate-500 hover:text-orange-500 transition-colors">
                  <Phone size={16} className="text-orange-500" />
                  <span>+880 1234 567 890</span>
                </a>
              </li>
              <li>
                <a href="mailto:hello@kitchenflow.com" className="flex items-center gap-3 text-sm text-slate-500 hover:text-orange-500 transition-colors">
                  <Mail size={16} className="text-orange-500" />
                  <span>hello@kitchenflow.com</span>
                </a>
              </li>
            </ul>
          </div>

        </div>

        {/* Bottom Bar */}
        <div className="pt-8 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
          <p className="text-xs text-slate-400 font-medium">
            © {currentYear} KitchenFlow. All rights reserved.
          </p>
          <div className="flex gap-6">
            <Link href="#" className="text-xs text-slate-400 hover:text-slate-600 transition-colors">Privacy Policy</Link>
            <Link href="#" className="text-xs text-slate-400 hover:text-slate-600 transition-colors">Terms of Service</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
