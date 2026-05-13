'use client';

import { useEffect, useState } from 'react';
import { StockResult } from '@/lib/services/ai';

const SLOT_COLORS = ['#f97316', '#3b82f6', '#8b5cf6', '#22c55e', '#f59e0b'];

const CX       = 90;
const CY       = 90;
const FACE_R   = 64;
const RING_OUT = 82;   // outer edge of arc ring
const RING_MID = 75;   // split point for overlapping pairs
const RING_IN  = 68;   // inner edge of arc ring

// ── Geometry ────────────────────────────────────────────────
function polarToXY(angleDeg: number, r: number) {
  const rad = (angleDeg - 90) * (Math.PI / 180);
  return { x: CX + r * Math.cos(rad), y: CY + r * Math.sin(rad) };
}

function donutArcPath(
  startDeg: number, endDeg: number,
  outerR: number,   innerR: number,
): string {
  // Normalise span so wrapping arcs (e.g. 330°→60°) work correctly
  const span  = ((endDeg - startDeg) + 360) % 360;
  const large = span > 180 ? 1 : 0;
  const normEnd = startDeg + span; // use unwrapped end for arc command

  const s1 = polarToXY(startDeg, outerR);
  const e1 = polarToXY(normEnd,  outerR);
  const s2 = polarToXY(normEnd,  innerR);
  const e2 = polarToXY(startDeg, innerR);

  return [
    `M ${s1.x} ${s1.y}`,
    `A ${outerR} ${outerR} 0 ${large} 1 ${e1.x} ${e1.y}`,
    `L ${s2.x} ${s2.y}`,
    `A ${innerR} ${innerR} 0 ${large} 0 ${e2.x} ${e2.y}`,
    'Z',
  ].join(' ');
}

// ── 12-hour clock mapping ────────────────────────────────────
// h in 0-23 → degrees on a 12-hr face (0° = 12 o'clock / top)
function hourToDeg12(h: number) { return (h % 12) * 30; }

// ── Parse "8:00 AM – 11:00 AM" → { startH, endH } (0-23) ───
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

// ── Analog Clock ─────────────────────────────────────────────
function AnalogClock({ slots, activeIdx }: {
  slots: { label: string }[];
  activeIdx: number | null;
}) {
  const [now, setNow] = useState(new Date());
  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(id);
  }, []);

  // Hands
  const totalH = now.getHours() % 12 + now.getMinutes() / 60;
  const totalM = now.getMinutes() + now.getSeconds() / 60;
  const hAngle = totalH * 30;
  const mAngle = totalM * 6;
  const sAngle = now.getSeconds() * 6;

  const handPath = (angleDeg: number, length: number) => {
    const tip  = polarToXY(angleDeg, length);
    const tail = polarToXY(angleDeg + 180, 10);
    return `M ${tail.x} ${tail.y} L ${tip.x} ${tip.y}`;
  };

  // Tick marks
  const ticks = Array.from({ length: 60 }, (_, i) => {
    const isMajor = i % 5 === 0;
    const inner   = polarToXY(i * 6, isMajor ? FACE_R - 7 : FACE_R - 4);
    const outer   = polarToXY(i * 6, FACE_R);
    return { inner, outer, isMajor };
  });

  // Parse slots & determine ring splits for overlapping pairs
  const parsedSlots = slots.map(s => parseSlotHours(s.label));

  // Each slot defaults to full ring; if two share the same 12hr position, split
  const ringOuter = slots.map(() => RING_OUT);
  const ringInner = slots.map(() => RING_IN);

  for (let i = 0; i < parsedSlots.length; i++) {
    for (let j = i + 1; j < parsedSlots.length; j++) {
      const a = parsedSlots[i];
      const b = parsedSlots[j];
      if (!a || !b) continue;
      if (a.startH % 12 === b.startH % 12 && a.endH % 12 === b.endH % 12) {
        // Overlapping pair — outer half for first, inner half for second
        ringOuter[i] = RING_OUT;  ringInner[i] = RING_MID;
        ringOuter[j] = RING_MID;  ringInner[j] = RING_IN;
      }
    }
  }

  return (
    <svg width={180} height={180} viewBox="0 0 180 180" style={{ overflow: 'visible' }}>

      {/* Dead-zone track (full ring, grey) */}
      <circle
        cx={CX} cy={CY}
        r={(RING_OUT + RING_IN) / 2}
        fill="none"
        stroke="rgba(255, 255, 255, 0.05)"
        strokeWidth={RING_OUT - RING_IN}
      />

      {/* Colored slot arcs */}
      {slots.map((slot, i) => {
        const p = parsedSlots[i];
        if (!p) return null;
        const startDeg = hourToDeg12(p.startH);
        const endDeg   = hourToDeg12(p.endH);
        const color    = SLOT_COLORS[i % SLOT_COLORS.length];
        return (
          <path
            key={slot.label}
            d={donutArcPath(startDeg, endDeg, ringOuter[i], ringInner[i])}
            fill={color}
            opacity={activeIdx === null || activeIdx === i ? 1 : 0.55}
          />
        );
      })}

      {/* Clock face */}
      <circle cx={CX} cy={CY} r={FACE_R} fill="#1e293b" />
      <circle cx={CX} cy={CY} r={FACE_R} fill="none" stroke="rgba(255, 255, 255, 0.1)" strokeWidth="1" />

      {/* Tick marks */}
      {ticks.map((t, i) => (
        <line
          key={i}
          x1={t.inner.x} y1={t.inner.y}
          x2={t.outer.x} y2={t.outer.y}
          stroke={t.isMajor ? '#94a3b8' : 'rgba(255, 255, 255, 0.1)'}
          strokeWidth={t.isMajor ? 1.5 : 1}
        />
      ))}

      {/* Hour numerals at 12, 3, 6, 9 */}
      {[{ l: '12', d: 0 }, { l: '3', d: 90 }, { l: '6', d: 180 }, { l: '9', d: 270 }].map(({ l, d }) => {
        const p = polarToXY(d, FACE_R - 14);
        return (
          <text key={l} x={p.x} y={p.y + 4}
            textAnchor="middle" fontSize="10" fontWeight="700"
            fill="#94a3b8" fontFamily="Inter, sans-serif">
            {l}
          </text>
        );
      })}

      {/* Hour hand */}
      <path d={handPath(hAngle, FACE_R - 22)} stroke="#fff" strokeWidth="3" strokeLinecap="round" fill="none" />
      {/* Minute hand */}
      <path d={handPath(mAngle, FACE_R - 12)} stroke="#cbd5e1" strokeWidth="2" strokeLinecap="round" fill="none" />
      {/* Second hand */}
      <path d={handPath(sAngle, FACE_R - 8)}  stroke="#f97316" strokeWidth="1" strokeLinecap="round" fill="none" />

      {/* Center dot */}
      <circle cx={CX} cy={CY} r="3.5" fill="#fff" />
      <circle cx={CX} cy={CY} r="1.5" fill="#f97316" />
    </svg>
  );
}

// ── Panel ────────────────────────────────────────────────────
export default function StockRecommendationPanel({ data }: { data: StockResult }) {
  if (!data.slots.length) {
    return <div className="ai-state">No upcoming time slots for today.</div>;
  }

  const nowH = new Date().getHours() + new Date().getMinutes() / 60;
  const activeIdx = data.slots.findIndex(slot => {
    const p = parseSlotHours(slot.label);
    return p ? nowH >= p.startH && nowH < p.endH : false;
  });

  const slots = data.slots.slice(0, 5);

  // Left col: indices 0, 4    Right col: indices 1, 2, 3
  const leftSlots  = ([0, 4]       as number[]).filter(i => i < slots.length);
  const rightSlots = ([1, 2, 3]    as number[]).filter(i => i < slots.length);

  const SlotCard = ({ idx }: { idx: number }) => {
    const slot    = slots[idx];
    const color   = SLOT_COLORS[idx % SLOT_COLORS.length];
    const isActive = activeIdx === idx;
    return (
      <div className="ai-clock-card" style={{
        borderLeftColor: color,
        background: isActive ? `${color}15` : 'rgba(255, 255, 255, 0.03)',
      }}>
        <div className="ai-clock-card-label" style={{ color }}>
          {slot.label}
          {isActive && <span className="ai-clock-card-active-dot" style={{ background: color }} />}
        </div>
        <div className="ai-clock-card-items">
          {slot.items.slice(0, 5).map(item => (
            <div className="ai-clock-card-row" key={item.name} style={{ borderBottomColor: 'rgba(255, 255, 255, 0.03)' }}>
              <span className="ai-clock-card-name" style={{ color: '#e2e8f0' }}>{item.name}</span>
              <span className="ai-clock-card-qty" style={{ color, background: `${color}18` }}>
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
      <div className="ai-clock-col">{leftSlots.map(i => <SlotCard key={i} idx={i} />)}</div>
      <div className="ai-clock-center"><AnalogClock slots={slots} activeIdx={activeIdx} /></div>
      <div className="ai-clock-col">{rightSlots.map(i => <SlotCard key={i} idx={i} />)}</div>
    </div>
  );
}