'use client';

import { useState, useEffect, useRef } from 'react';
import { predictItemAffinity, searchMenuItems, PredictiveAffinityResult } from '@/lib/services/ai';

export default function AffinityPanel() {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<{ id: number; name: string }[]>([]);
  const [selectedItem, setSelectedItem] = useState<{ id: number; name: string } | null>(null);
  const [results, setResults] = useState<PredictiveAffinityResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [searching, setSearching] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Autocomplete logic
  useEffect(() => {
    if (query.length < 2 || selectedItem) {
      if (!selectedItem) {
        setSuggestions([]);
        setShowDropdown(false);
      }
      return;
    }

    const delay = setTimeout(async () => {
      setSearching(true);
      const { data } = await searchMenuItems(query);
      setSuggestions(data || []);
      setShowDropdown((data || []).length > 0);
      setSearching(false);
    }, 300);

    return () => clearTimeout(delay);
  }, [query, selectedItem]);

  // Handle click outside to close dropdown
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setShowDropdown(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSearch = async () => {
    if (!selectedItem) return;
    setLoading(true);
    const { data } = await predictItemAffinity(selectedItem.id);
    setResults(data);
    setLoading(false);
  };

  const selectItem = (item: { id: number; name: string }) => {
    setSelectedItem(item);
    setQuery(item.name);
    setSuggestions([]);
    setShowDropdown(false);
  };

  const clearSearch = () => {
    setQuery('');
    setSelectedItem(null);
    setResults(null);
    setSuggestions([]);
  };

  return (
    <div className="ai-affinity-container">
      <div className="ai-affinity-search-wrap">
        <div className="ai-affinity-input-group" ref={dropdownRef}>
          <div className="ai-affinity-input-inner">
             <input
                type="text"
                placeholder="Search menu item (e.g. French Fries)..."
                value={query}
                onChange={(e) => {
                    setQuery(e.target.value);
                    if (selectedItem && e.target.value !== selectedItem.name) {
                        setSelectedItem(null);
                    }
                }}
                onFocus={() => {
                    if (query.length >= 2 && suggestions.length > 0) {
                        setShowDropdown(true);
                    }
                }}
                className="ai-affinity-input"
             />
             {query && (
                 <button className="ai-affinity-clear" onClick={clearSearch}>×</button>
             )}
          </div>
          
          {showDropdown && (
            <div className="ai-affinity-dropdown">
              {suggestions.map(item => (
                <div 
                  key={item.id} 
                  className="ai-affinity-dropdown-item"
                  onClick={() => selectItem(item)}
                >
                  {item.name}
                </div>
              ))}
              {searching && (
                  <div className="ai-affinity-dropdown-item" style={{ opacity: 0.5 }}>Searching...</div>
              )}
            </div>
          )}
        </div>

        <button 
          className="ai-affinity-btn" 
          onClick={handleSearch}
          disabled={!selectedItem || loading}
        >
          {loading ? 'Analyzing...' : 'Analyze Affinity'}
        </button>
      </div>

      <div className="ai-affinity-results">
        {loading ? (
            <div className="ai-state">Computing patterns from the last 14 days...</div>
        ) : results ? (
            <div className="ai-affinity-detail">
                <div className="ai-affinity-anchor">
                    <span className="ai-affinity-anchor-label">Anchor Item</span>
                    <h3 className="ai-affinity-anchor-name">🍽 {results.anchor.name}</h3>
                </div>

                <div className="ai-affinity-companions-list">
                    {results.predictions && results.predictions.length > 0 ? (
                        results.predictions.map((p, i) => (
                            <div className="ai-affinity-row" key={p.id} style={{ animationDelay: `${i * 0.1}s` }}>
                                <div className="ai-affinity-row-info">
                                    <span className="ai-affinity-row-name">{p.name}</span>
                                    <span className="ai-affinity-row-count">{p.count} co-orders</span>
                                </div>
                                <div className="ai-affinity-bar-wrap">
                                    <div className="ai-affinity-bar-fill" style={{ width: `${p.rate}%` }}>
                                        <span className="ai-affinity-bar-pct">{p.rate}%</span>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="ai-state">No significant affinity patterns found for this item in the last 14 days.</div>
                    )}
                </div>
            </div>
        ) : (
            <div className="ai-state">Search for an item to see its predicted companions based on 14-day history.</div>
        )}
      </div>
    </div>
  );
}