'use client';

import { AffinityResult } from '@/lib/services/ai';

export default function AffinityPanel({ data }: { data: AffinityResult }) {
  return (
    <div className="ai-affinity-list">
      {data.anchors.map(anchor => (
        <div className="ai-anchor-card" key={anchor.anchor}>
          <div className="ai-anchor-name">🍽 {anchor.anchor}</div>
          <div className="ai-companions">
            {anchor.companions.map(c => (
              <span className="ai-companion-tag" key={c.name}>
                {c.name} <span className="ai-companion-count">×{c.count}</span>
              </span>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}