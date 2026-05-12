'use client';

import { useState } from 'react';
import { RevenueForecastResult } from '@/lib/services/ai';

const W = 860;
const H = 300;
const PAD = { top: 20, right: 24, bottom: 40, left: 72 };
const INNER_W = W - PAD.left - PAD.right;
const INNER_H = H - PAD.top - PAD.bottom;

function fmtFull(n: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency', currency: 'USD', maximumFractionDigits: 0,
  }).format(n);
}

function fmtCompact(n: number): string {
  if (n === 0) return '$0';
  if (n >= 1_000_000) return `$${(n / 1_000_000).toFixed(n % 1_000_000 === 0 ? 0 : 1)}M`;
  if (n >= 1_000) return `$${(n / 1_000).toFixed(n % 1_000 === 0 ? 0 : 1)}K`;
  return `$${n}`;
}

function niceScale(maxVal: number, tickCount = 4): { niceMax: number; ticks: number[] } {
  if (maxVal <= 0) return { niceMax: 100, ticks: [0, 25, 50, 75, 100] };
  const rawStep = maxVal / tickCount;
  const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
  const residual = rawStep / magnitude;
  let niceResidual: number;
  if (residual <= 1) niceResidual = 1;
  else if (residual <= 2) niceResidual = 2;
  else if (residual <= 2.5) niceResidual = 2.5;
  else if (residual <= 5) niceResidual = 5;
  else niceResidual = 10;
  const step = niceResidual * magnitude;
  const niceMax = Math.ceil(maxVal / step) * step;
  const ticks = Array.from({ length: Math.round(niceMax / step) + 1 }, (_, i) => i * step);
  return { niceMax, ticks };
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function parsePeriod(period: string) {
  const match = period.match(/^(\d{4})-(\d{2})\s+W([12])$/);
  if (!match) return { mon: period, yr: '', half: '1', key: period };
  const [, year, month, half] = match;
  return {
    mon: MONTHS[parseInt(month, 10) - 1] ?? month,
    yr: `'${year.slice(2)}`,
    half,
    key: `${year}-${month}`,   // e.g. "2026-04" — groups both halves
  };
}

function fmtCardLabel(period: string): string {
  const { mon, yr, half } = parsePeriod(period);
  return `${mon} ${yr} · ${half === '1' ? '1st Half' : '2nd Half'}`;
}

export default function RevenueChart({ data }: { data: RevenueForecastResult }) {
  const [hoveredIdx, setHoveredIdx] = useState<number | null>(null);
  const all = [...data.historical, ...data.forecast];
  const maxRev = Math.max(...all.map(d => d.revenue));

  const { niceMax, ticks } = niceScale(maxRev, 4);

  const toX = (i: number) =>
    all.length > 1
      ? PAD.left + (i / (all.length - 1)) * INNER_W
      : PAD.left + INNER_W / 2;

  const toY = (v: number) =>
    PAD.top + INNER_H - (v / niceMax) * INNER_H;

  const histPts = data.historical.map((d, i) => `${toX(i)},${toY(d.revenue)}`).join(' ');
  const forecastStart = data.historical.length - 1;
  const forecastPts = [data.historical.at(-1)!, ...data.forecast]
    .map((d, i) => `${toX(forecastStart + i)},${toY(d.revenue)}`).join(' ');

  const lastHistorical = data.historical.at(-1);
  const chartBottom = PAD.top + INNER_H;

  // ── Build month label positions ─────────────────────────────
  // Group indices by month key. Only show label when BOTH halves present.
  const monthGroups: Record<string, { indices: number[]; mon: string; yr: string }> = {};
  all.forEach((d, i) => {
    const { mon, yr, key } = parsePeriod(d.period);
    if (!monthGroups[key]) monthGroups[key] = { indices: [], mon, yr };
    monthGroups[key].indices.push(i);
  });

  const monthLabels = Object.values(monthGroups)
    .filter(g => g.indices.length === 2)   // both halves must exist
    .map(g => ({
      x: (toX(g.indices[0]) + toX(g.indices[1])) / 2,  // center between the two dots
      label: `${g.mon} ${g.yr}`,
    }));

  const getTooltipPos = (idx: number) => {
    const isHist = idx < data.historical.length;
    const val = isHist
      ? data.historical[idx].revenue
      : data.forecast[idx - data.historical.length].revenue;
    return { x: toX(idx), y: toY(val), val };
  };

  return (
    <div className="ai-revenue-container">

      {/* ── Summary cards + legend ────────────────────────── */}
      <div className="ai-revenue-meta">
        <div className="ai-metric">
          <span className="ai-metric-label">
            {lastHistorical ? fmtCardLabel(lastHistorical.period) : 'Last Period'}
          </span>
          <span className="ai-metric-value">{fmtFull(lastHistorical?.revenue ?? 0)}</span>
        </div>

        {data.forecast.map((f) => (
          <div className="ai-metric" key={f.period}>
            <span className="ai-metric-label">
              {fmtCardLabel(f.period)}
              <span className="ai-metric-sublabel">&nbsp;· Forecast</span>
            </span>
            <span className="ai-metric-value">{fmtFull(f.revenue)}</span>
            <span className={`ai-metric-pct ${f.pct_change >= 0 ? 'up' : 'down'}`}>
              {f.pct_change >= 0 ? '▲' : '▼'} {Math.abs(f.pct_change)}%
            </span>
          </div>
        ))}

        {/* Legend — right-aligned */}
        <div className="ai-chart-legend ai-chart-legend--inline">
          <span><span className="ai-legend-dot" style={{ background: '#f97316' }} />Historical</span>
          <span><span className="ai-legend-dot" style={{ background: '#3b82f6' }} />Forecast</span>
        </div>
      </div>

      {/* ── SVG chart ─────────────────────────────────────── */}
      <div className="ai-chart-wrapper">
        <svg viewBox={`0 0 ${W} ${H}`} className="ai-chart-svg">

          {/* Y grid + labels */}
          {ticks.map(tick => {
            const y = toY(tick);
            return (
              <g key={tick}>
                <line
                  x1={PAD.left} x2={W - PAD.right} y1={y} y2={y}
                  stroke={tick === 0 ? '#cbd5e1' : '#f1f5f9'}
                  strokeWidth={tick === 0 ? 1.5 : 1}
                />
                <text
                  x={PAD.left - 10} y={y + 4}
                  textAnchor="end" fontSize="12"
                  fill="#0f172a" fontFamily="Inter, sans-serif" fontWeight="600"
                >
                  {fmtCompact(tick)}
                </text>
              </g>
            );
          })}

          {/* Forecast separator */}
          <line
            x1={toX(forecastStart)} x2={toX(forecastStart)}
            y1={PAD.top} y2={chartBottom}
            stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4 3"
          />

          {/* Lines */}
          <polyline points={histPts} fill="none" stroke="#f97316" strokeWidth="2.5" strokeLinejoin="round" opacity="0.9" />
          <polyline points={forecastPts} fill="none" stroke="#3b82f6" strokeWidth="2.5" strokeDasharray="6 4" strokeLinejoin="round" opacity="0.8" />

          {/* Historical dots */}
          {data.historical.map((d, i) => {
            const hov = hoveredIdx === i;
            return (
              <circle key={`h-${i}`}
                cx={toX(i)} cy={toY(d.revenue)} r={hov ? 6 : 4}
                fill="#f97316"
                stroke={hov ? '#fff' : 'none'} strokeWidth={2}
                style={{ cursor: 'pointer', transition: 'r 0.15s' }}
                onMouseEnter={() => setHoveredIdx(i)}
                onMouseLeave={() => setHoveredIdx(null)}
              />
            );
          })}

          {/* Forecast dots */}
          {data.forecast.map((d, i) => {
            const gi = data.historical.length + i;
            const hov = hoveredIdx === gi;
            return (
              <circle key={`f-${i}`}
                cx={toX(forecastStart + 1 + i)} cy={toY(d.revenue)} r={hov ? 6 : 4}
                fill="#3b82f6"
                stroke={hov ? '#fff' : 'none'} strokeWidth={2}
                style={{ cursor: 'pointer', transition: 'r 0.15s' }}
                onMouseEnter={() => setHoveredIdx(gi)}
                onMouseLeave={() => setHoveredIdx(null)}
              />
            );
          })}

          {/* X-axis month labels — centered between each month's two halves */}
          {monthLabels.map(({ x, label }) => (
            <text
              key={label} x={x} y={chartBottom + 18}
              textAnchor="middle" fontSize="11"
              fill="#0f172a" fontFamily="Inter, sans-serif" fontWeight="600"
              style={{ pointerEvents: 'none' }}
            >
              {label}
            </text>
          ))}
        </svg>

        {/* Tooltip */}
        {hoveredIdx !== null && (() => {
          const isHist = hoveredIdx < data.historical.length;
          const period = isHist
            ? data.historical[hoveredIdx].period
            : data.forecast[hoveredIdx - data.historical.length].period;
          const { x, y } = getTooltipPos(hoveredIdx);
          const val = isHist
            ? data.historical[hoveredIdx].revenue
            : data.forecast[hoveredIdx - data.historical.length].revenue;

          // If dot is in last 20% of chart width, anchor tooltip to the right of its position
          // so it doesn't bleed outside the container
          const nearRightEdge = x / W > 0.80;
          const xPct = (x / W) * 100;
          const yPct = (y / H) * 100;

          return (
            <div className="ai-chart-tooltip" style={{
              left: nearRightEdge ? 'auto' : `${xPct}%`,
              right: nearRightEdge ? `${100 - xPct}%` : 'auto',
              top: `${yPct}%`,
              transform: nearRightEdge ? 'translate(0, -120%)' : 'translate(-50%, -120%)',
              textAlign: 'center',
            }}>
              <div className="ai-tooltip-label">{`${parsePeriod(period).mon} ${parsePeriod(period).yr}`}</div>
              <div className="ai-tooltip-label" style={{ opacity: 0.6, marginTop: '1px' }}>
                {parsePeriod(period).half === '1' ? '1st Half' : '2nd Half'}
              </div>
              <div className="ai-tooltip-value">{fmtFull(val)}</div>
            </div>
          );
        })()}
      </div>
    </div>
  );
}