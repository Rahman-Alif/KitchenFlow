'use client';

import { useState } from 'react';
import { DemandForecastResult } from '@/lib/services/ai';

const COLORS = [
  '#f97316', '#3b82f6', '#8b5cf6', '#22c55e', '#ef4444',
  '#f59e0b', '#06b6d4', '#ec4899', '#14b8a6', '#a855f7',
];

const FALLBACK_COLOR = '#a1a1aa';

export default function DemandChart({ data }: { data: DemandForecastResult }) {
  const [hoveredIdx, setHoveredIdx] = useState<number | null>(null);

  const items = [...data.items]
    .sort((a, b) => b.next_month_prediction - a.next_month_prediction)
    .slice(0, 8);

  const total = items.reduce((sum, item) => sum + item.next_month_prediction, 0);

  // Donut geometry — same approach as DashboardOverview
  const SIZE = 280;
  const STROKE = 32;
  const R = (SIZE - STROKE) / 2;
  const CIRC = 2 * Math.PI * R;
  const cx = SIZE / 2;
  const cy = SIZE / 2;

  let offset = 0;

  const arcs = items.map((item, i) => {
    const pct = total > 0 ? item.next_month_prediction / total : 0;
    const dash = pct * CIRC;
    const gap = CIRC - dash;
    const arc = { item, dash, gap, offset, color: COLORS[i % COLORS.length] ?? FALLBACK_COLOR, index: i };
    offset += dash;
    return arc;
  });

  return (
    <div className="ai-donut-wrapper">
      <div className="ai-donut-chart-area">
        <svg width={SIZE} height={SIZE} viewBox={`0 0 ${SIZE} ${SIZE}`}>
          {/* Background track */}
          <circle cx={cx} cy={cy} r={R} fill="none" stroke="rgba(255, 255, 255, 0.05)" strokeWidth={STROKE} />

          {/* Arcs */}
          {arcs.map((arc) => (
            <circle
              key={arc.item.name}
              cx={cx} cy={cy} r={R}
              fill="none"
              stroke={arc.color}
              strokeWidth={hoveredIdx === arc.index ? STROKE + 4 : STROKE}
              strokeDasharray={`${arc.dash} ${arc.gap}`}
              strokeDashoffset={-arc.offset}
              className="ai-donut-arc"
              style={{
                transform: 'rotate(-90deg)',
                transformOrigin: 'center',
                opacity: hoveredIdx === null || hoveredIdx === arc.index ? 1 : 0.4,
                cursor: 'pointer',
              }}
              onMouseEnter={() => setHoveredIdx(arc.index)}
              onMouseLeave={() => setHoveredIdx(null)}
            />
          ))}

          {/* Center text */}
          <text x={cx} y={cy - 8} textAnchor="middle" className="ai-donut-total-num">
            {hoveredIdx !== null ? items[hoveredIdx].next_month_prediction : total}
          </text>
          <text x={cx} y={cy + 10} textAnchor="middle" className="ai-donut-total-label">
            {hoveredIdx !== null ? 'portions' : 'total'}
          </text>
        </svg>

        {/* Tooltip on hover */}
        {hoveredIdx !== null && (
          <div className="ai-donut-tooltip">
            <div className="ai-donut-tooltip-name">{items[hoveredIdx].name}</div>
            <div className="ai-donut-tooltip-val">
              {items[hoveredIdx].next_month_prediction} portions
              <span className="ai-donut-tooltip-pct">
                {' '}· {total > 0 ? Math.round((items[hoveredIdx].next_month_prediction / total) * 100) : 0}%
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Legend */}
      <ul className="ai-donut-legend">
        {arcs.map((arc) => (
          <li
            key={arc.item.name}
            className="ai-donut-legend-item"
            onMouseEnter={() => setHoveredIdx(arc.index)}
            onMouseLeave={() => setHoveredIdx(null)}
            style={{
              cursor: 'pointer',
              opacity: hoveredIdx === null || hoveredIdx === arc.index ? 1 : 0.4,
            }}
          >
            <span className="ai-donut-legend-dot" style={{ background: arc.color }} />
            <strong className="ai-donut-legend-count">{arc.item.next_month_prediction}</strong>
            <span className="ai-donut-legend-label">{arc.item.name}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}