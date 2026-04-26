'use client';

import React, { useState, useEffect } from 'react';
import { 
  getMessages, 
  sendMessage, 
  markMessageRead, 
  Message, 
  PaginatedMessages 
} from '@/lib/services/messages';
import { getUsers, AdminUser } from '@/lib/services/users';
import { getUser, AuthUser } from '@/lib/auth';

function formatDateTime(dt: string): string {
  return new Date(dt).toLocaleString('en-GB', {
    day:    '2-digit',
    month:  'short',
    year:   'numeric',
    hour:   '2-digit',
    minute: '2-digit',
  });
}

function tagLabel(tag: string): string {
  return tag.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

export default function MessagesPortal() {
  const [messages, setMessages] = useState<Message[]>([]);
  const [meta, setMeta] = useState<PaginatedMessages | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Filters
  const [priority, setPriority] = useState('');
  const [tag, setTag] = useState('');

  const [expandedId, setExpandedId] = useState<number | null>(null);

  // Modal State
  const [showModal, setShowModal] = useState(false);
  const [staffUsers, setStaffUsers] = useState<AdminUser[]>([]);
  const [newMsg, setNewMsg] = useState({
    receiver_id: '',
    title: '',
    content: '',
    priority: 'low',
    tag: 'other'
  });
  const [sending, setSending] = useState(false);
  const [currentUser, setCurrentUser] = useState<AuthUser | null>(null);

  function isUnreadForMe(msg: Message) {
    return !msg.is_read && currentUser?.id === msg.receiver_id;
  }

  async function fetchMessages(page = 1) {
    setLoading(true);
    const { data, error } = await getMessages({ priority, tag, page });
    if (error || !data) {
      setError(error ?? 'Failed to load messages');
      setMessages([]);
      setMeta(null);
    } else {
      setMessages(data.data);
      setMeta(data);
    }
    setLoading(false);
  }

  useEffect(() => {
    setCurrentUser(getUser());
    fetchMessages();
    getUsers().then(res => {
      if (res.data) setStaffUsers(res.data.filter(u => u.is_active));
    });
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  function handleSearch() {
    fetchMessages(1);
  }

  async function handleExpand(msg: Message) {
    const isExpanded = expandedId === msg.id;
    setExpandedId(isExpanded ? null : msg.id);

    if (!isExpanded && isUnreadForMe(msg)) {
      // Optimistic update
      setMessages(prev => prev.map(m => m.id === msg.id ? { ...m, is_read: true } : m));
      const res = await markMessageRead(msg.id);
      if (res.error) {
        // Revert on error
        setMessages(prev => prev.map(m => m.id === msg.id ? { ...m, is_read: false } : m));
      }
    }
  }

  async function handleSend(e: React.FormEvent) {
    e.preventDefault();
    if (!newMsg.receiver_id || !newMsg.title || !newMsg.content) return;
    
    setSending(true);
    const res = await sendMessage({
      receiver_id: Number(newMsg.receiver_id),
      title: newMsg.title,
      content: newMsg.content,
      priority: newMsg.priority,
      tag: newMsg.tag
    });
    
    setSending(false);
    if (!res.error) {
      setShowModal(false);
      setNewMsg({ receiver_id: '', title: '', content: '', priority: 'low', tag: 'other' });
      fetchMessages(1);
    } else {
      alert(res.error);
    }
  }

  const pageNumbers = meta ? Array.from({ length: meta.last_page }, (_, i) => i + 1) : [];

  return (
    <section className="adm-msg">
      <div className="adm-msg-filters">
        <div className="adm-msg-filters-left">
          <select className="adm-msg-select" value={priority} onChange={e => setPriority(e.target.value)}>
            <option value="">All Priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
          <select className="adm-msg-select" value={tag} onChange={e => setTag(e.target.value)}>
            <option value="">All Tags</option>
            <option value="item_requirement">Item Requirement</option>
            <option value="customer_inquiry">Customer Inquiry</option>
            <option value="staff_duty">Staff Duty</option>
            <option value="incident">Incident</option>
            <option value="other">Other</option>
          </select>
          <button className="adm-msg-btn" onClick={handleSearch} disabled={loading}>
            {loading ? 'Searching...' : 'Search'}
          </button>
        </div>
        <button className="adm-msg-btn" onClick={() => setShowModal(true)}>
          + New Message
        </button>
      </div>

      {error && <p style={{ color: '#ef4444', fontSize: '13px', marginBottom: '12px' }}>{error}</p>}

      <div className="adm-msg-table-wrap">
        <table className="adm-msg-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>From</th>
              <th>To</th>
              <th>Title</th>
              <th>Tags</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr className="adm-msg-state-row">
                <td colSpan={5}>Loading messages...</td>
              </tr>
            ) : messages.length === 0 ? (
              <tr className="adm-msg-state-row">
                <td colSpan={5}>No messages found.</td>
              </tr>
            ) : (
              messages.map(msg => (
                <React.Fragment key={msg.id}>
                  <tr 
                    className={`adm-msg-row adm-msg-row--clickable ${isUnreadForMe(msg) ? 'adm-msg-row--unread' : ''} ${expandedId === msg.id ? 'adm-msg-row--expanded' : ''}`}
                    onClick={() => handleExpand(msg)}
                  >
                    <td>{formatDateTime(msg.created_at)}</td>
                    <td>{msg.sender.name}</td>
                    <td>{msg.receiver.name}</td>
                    <td>
                      {isUnreadForMe(msg) && <span style={{ color: '#3b82f6', marginRight: '6px' }}>●</span>}
                      {msg.title}
                    </td>
                    <td>
                      <span className={`adm-msg-badge adm-msg-badge--${msg.priority}`} style={{ marginRight: '6px' }}>
                        {msg.priority}
                      </span>
                      <span className="adm-msg-badge adm-msg-badge--tag">
                        {tagLabel(msg.tag)}
                      </span>
                    </td>
                  </tr>
                  {expandedId === msg.id && (
                    <tr className="adm-msg-detail">
                      <td colSpan={5}>
                        <div className="adm-msg-detail-title">Message Content</div>
                        <p className="adm-msg-content">{msg.content}</p>
                      </td>
                    </tr>
                  )}
                </React.Fragment>
              ))
            )}
          </tbody>
        </table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="adm-msg-pagination">
          <button
            className="adm-msg-page-btn"
            onClick={() => fetchMessages(meta.current_page - 1)}
            disabled={meta.current_page === 1 || loading}
          >
            ←
          </button>
          {pageNumbers.map(page => (
            <button
              key={page}
              className={`adm-msg-page-btn ${meta.current_page === page ? 'adm-msg-page-btn--active' : ''}`}
              onClick={() => fetchMessages(page)}
              disabled={loading}
            >
              {page}
            </button>
          ))}
          <button
            className="adm-msg-page-btn"
            onClick={() => fetchMessages(meta.current_page + 1)}
            disabled={meta.current_page === meta.last_page || loading}
          >
            →
          </button>
        </div>
      )}

      {/* New Message Modal */}
      {showModal && (
        <div className="adm-msg-modal-overlay">
          <div className="adm-msg-modal">
            <h2 className="adm-msg-modal-title">Send Message</h2>
            <form onSubmit={handleSend}>
              <div className="adm-msg-form-group">
                <label>To (Staff Member)</label>
                <select 
                  className="adm-msg-select" 
                  value={newMsg.receiver_id} 
                  onChange={e => setNewMsg({...newMsg, receiver_id: e.target.value})}
                  required
                >
                  <option value="" disabled>Select a staff member</option>
                  {staffUsers.map(u => (
                    <option key={u.id} value={u.id}>{u.name} ({u.role})</option>
                  ))}
                </select>
              </div>
              <div className="adm-msg-form-group">
                <label>Title</label>
                <input 
                  type="text" 
                  className="adm-msg-input" 
                  value={newMsg.title} 
                  onChange={e => setNewMsg({...newMsg, title: e.target.value})}
                  required
                  placeholder="Subject of message"
                />
              </div>
              <div style={{ display: 'flex', gap: '16px', marginBottom: '16px' }}>
                <div className="adm-msg-form-group" style={{ flex: 1, marginBottom: 0 }}>
                  <label>Priority</label>
                  <select 
                    className="adm-msg-select" 
                    value={newMsg.priority} 
                    onChange={e => setNewMsg({...newMsg, priority: e.target.value})}
                  >
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                  </select>
                </div>
                <div className="adm-msg-form-group" style={{ flex: 1, marginBottom: 0 }}>
                  <label>Tag</label>
                  <select 
                    className="adm-msg-select" 
                    value={newMsg.tag} 
                    onChange={e => setNewMsg({...newMsg, tag: e.target.value})}
                  >
                    <option value="item_requirement">Item Requirement</option>
                    <option value="customer_inquiry">Customer Inquiry</option>
                    <option value="staff_duty">Staff Duty</option>
                    <option value="incident">Incident</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
              <div className="adm-msg-form-group">
                <label>Message Content</label>
                <textarea 
                  value={newMsg.content} 
                  onChange={e => setNewMsg({...newMsg, content: e.target.value})}
                  required
                  placeholder="Type your message here..."
                />
              </div>
              <div className="adm-msg-modal-actions">
                <button type="button" className="adm-msg-btn" style={{ background: 'transparent', color: 'var(--adm-text)', border: '1px solid var(--adm-border)' }} onClick={() => setShowModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="adm-msg-btn" disabled={sending}>
                  {sending ? 'Sending...' : 'Send Message'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </section>
  );
}
