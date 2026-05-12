'use client';

import { useEffect, useState } from 'react';
import { StockResult } from '@/lib/services/ai';

const SLOT_COLORS = ['#f97316', '#3b82f6', '#8b5cf6', '#22c55e', '#f59e0b'];
const CX = 90;
const CY = 90;
const OUTER_R = 82;   // arc ring
const INNER_R = 68;   // clock face edge
const FACE_R = 66;

// ── Geometry helpers ─────────────────────────────────────────
function polarToXY(angleDeg: number, r: number) {
  const rad = (angleDeg - 90) * (Math.PI / 180);
  return { x: CX + r * Math.cos(rad), y: CY + r * Math.sin(rad) };
}

function arcPath(startDeg: number, endDeg: number, r: number): string {
  const s = polarToXY(startDeg, r);
  const e = polarToXY(endDeg, r);
  const large = endDeg - startDeg > 180 ? 1 : 0;
  return `M ${s.x} ${s.y} A ${r} ${r} 0 ${large} 1 ${e.x} ${e.y}`;
}

function donutArcPath(startDeg: number, endDeg: number, outerR: number, innerR: number): string {
  const s1 = polarToXY(startDeg, outerR);
  const e1 = polarToXY(endDeg, outerR);
  const s2 = polarToXY(endDeg, innerR);
  const e2 = polarToXY(startDeg, innerR);
  const large = endDeg - startDeg > 180 ? 1 : 0;
  return [
    `M ${s1.x} ${s1.y}`,
    `A ${outerR} ${outerR} 0 ${large} 1 ${e1.x} ${e1.y}`,
    `L ${s2.x} ${s2.y}`,
    `A ${innerR} ${innerR} 0 ${large} 0 ${e2.x} ${e2.y}`,
    'Z',
  ].join(' ');
}

// ── Parse "8:00 AM - 11:00 AM" → { startH, endH } ───────────
function parseSlotHours(label: string): { startH: number; endH: number } | null {
  const m = label.match(/(\d+):(\d+)\s*(AM|PM)\s*[-–]\s*(\d+):(\d+)\s*(AM|PM)/i);
  if (!m) return null;
  let sh = parseInt(m[1]);
  let eh = parseInt(m[4]);
  const sap = m[3].toUpperCase();
  const eap = m[6].toUpperCase();
  if (sap === 'PM' && sh !== 12) sh += 12;
  if (sap === 'AM' && sh === 12) sh = 0;
  if (eap === 'PM' && eh !== 12) eh += 12;
  if (eap === 'AM' && eh === 12) eh = 0;
  return { startH: sh, endH: eh };
}

// 24h clock: hour 0 = 0°, hour 24 = 360°, 12 o'clock = top (−90° in SVG)
function hourToDeg(h: number) { return h * 15; }

// ── Analog clock component ───────────────────────────────────
function AnalogClock({ slots, activeIdx }: {
  slots: { label: string }[];
  activeIdx: number | null;
}) {
  const [now, setNow] = useState(new Date());

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(id);
  }, []);

  const h = now.getHours() % 12 + now.getMinutes() / 60;
  const m = now.getMinutes() + now.getSeconds() / 60;
  const s = now.getSeconds();
  const hA = h * 30;          // 360/12
  const mA = m * 6;           // 360/60
  const sA = s * 6;

  const SIZE = 180;

  // Tick marks
  const ticks = Array.from({ length: 60 }, (_, i) => {
    const isMajor = i % 5 === 0;
    const inner = polarToXY(i * 6, isMajor ? INNER_R - 6 : INNER_R - 3);
    const outer = polarToXY(i * 6, INNER_R);
    return { inner, outer, isMajor };
  });

  // Hour numerals at 12, 3, 6, 9
  const numerals = [
    { label: '12', deg: 0 },
    { label: '3', deg: 90 },
    { label: '6', deg: 180 },
    { label: '9', deg: 270 },
  ];

  const hand = (angleDeg: number, length: number) => {
    const tip = polarToXY(angleDeg, length);
    const tail = polarToXY(angleDeg + 180, 10);
    return `M ${tail.x} ${tail.y} L ${tip.x} ${tip.y}`;
  };

  return (
    <svg width={SIZE} height={SIZE} viewBox={`0 0 ${SIZE} ${SIZE}`} style={{ overflow: 'visible' }}>
      {/* ── Overnight dead-zone track ── */}
      <circle
        cx={CX} cy={CY} r={(OUTER_R + INNER_R) / 2}
        fill="none"
        stroke="#f1f5f9"
        strokeWidth={OUTER_R - INNER_R}
      />

      {/* ── Colored slot arcs ── */}
      {slots.map((slot, i) => {
        const parsed = parseSlotHours(slot.label);
        if (!parsed) return null;
        const startDeg = hourToDeg(parsed.startH);
        const endDeg = hourToDeg(parsed.endH);
        const color = SLOT_COLORS[i % SLOT_COLORS.length];
        const isActive = activeIdx === i;
        return (
          <path
            key={slot.label}
            d={donutArcPath(startDeg, endDeg, OUTER_R, INNER_R)}
            fill={color}
            opacity={isActive ? 1 : 0.75}
          />
        );
      })}

      {/* ── Clock face ── */}
      <circle cx={CX} cy={CY} r={FACE_R} fill="#fff" />
      <circle cx={CX} cy={CY} r={FACE_R} fill="none" stroke="#e2e8f0" strokeWidth="1" />

      {/* ── Tick marks ── */}
      {ticks.map((t, i) => (
        <line
          key={i}
          x1={t.inner.x} y1={t.inner.y}
          x2={t.outer.x} y2={t.outer.y}
          stroke={t.isMajor ? '#94a3b8' : '#e2e8f0'}
          strokeWidth={t.isMajor ? 1.5 : 1}
        />
      ))}

      {/* ── Hour numerals ── */}
      {numerals.map(({ label, deg }) => {
        const pos = polarToXY(deg, FACE_R - 14);
        return (
          <text
            key={label}
            x={pos.x} y={pos.y + 4}
            textAnchor="middle"
            fontSize="10"
            fontWeight="700"
            fill="#475569"
            fontFamily="Inter, sans-serif"
          >
            {label}
          </text>
        );
      })}

      {/* ── Hour hand ── */}
      <path
        d={hand(hA, FACE_R - 22)}
        stroke="#0f172a" strokeWidth="3"
        strokeLinecap="round" fill="none"
      />

      {/* ── Minute hand ── */}
      <path
        d={hand(mA, FACE_R - 12)}
        stroke="#0f172a" strokeWidth="2"
        strokeLinecap="round" fill="none"
      />

      {/* ── Second hand ── */}
      <path
        d={hand(sA, FACE_R - 8)}
        stroke="#f97316" strokeWidth="1"
        strokeLinecap="round" fill="none"
      />

      {/* ── Center dot ── */}
      <circle cx={CX} cy={CY} r="3.5" fill="#0f172a" />
      <circle cx={CX} cy={CY} r="1.5" fill="#f97316" />
    </svg>
  );
}

// ── Main panel ───────────────────────────────────────────────
export default function StockRecommendationPanel({ data }: { data: StockResult }) {
  if (!data.slots.length) {
    return <div className="ai-state">No upcoming time slots for today.</div>;
  }

  // Determine active slot based on current time
  const nowH = new Date().getHours() + new Date().getMinutes() / 60;
  const activeIdx = data.slots.findIndex(slot => {
    const parsed = parseSlotHours(slot.label);
    if (!parsed) return false;
    return nowH >= parsed.startH && nowH < parsed.endH;
  });

  const slots = data.slots.slice(0, 5);

  // Split: left gets indices [0, 4(if exists)], right gets [1, 2, 3]
  const leftSlots = [0, 4].filter(i => i < slots.length);
  const rightSlots = [1, 2, 3].filter(i => i < slots.length);

  const SlotCard = ({ idx }: { idx: number }) => {
    const slot = slots[idx];
    const color = SLOT_COLORS[idx % SLOT_COLORS.length];
    const isActive = activeIdx === idx;
    return (
      <div
        className="ai-clock-card"
        style={{
          borderLeftColor: color,
          background: isActive ? `${color}08` : '#fff',
        }}
      >
        <div className="ai-clock-card-label" style={{ color }}>
          {slot.label}
          {isActive && <span className="ai-clock-card-active-dot" style={{ background: color }} />}
        </div>
        <div className="ai-clock-card-items">
          {slot.items.slice(0, 5).map(item => (
            <div className="ai-clock-card-row" key={item.name}>
              <span className="ai-clock-card-name">{item.name}</span>
              <span className="ai-clock-card-qty" style={{ color, background: `${color}15` }}>
                {item.predicted_qty}
              </span>
            </div>
          ))}
        </div>
      </div>
    );
  };

  return (
    <div className="ai-clock-layout">
      {/* Left column */}
      <div className="ai-clock-col">
        {leftSlots.map(i => <SlotCard key={i} idx={i} />)}
      </div>

      {/* Center clock */}
      <div className="ai-clock-center">
        <AnalogClock slots={slots} activeIdx={activeIdx} />
      </div>

      {/* Right column */}
      <div className="ai-clock-col">
        {rightSlots.map(i => <SlotCard key={i} idx={i} />)}
      </div>
    </div>
  );
}