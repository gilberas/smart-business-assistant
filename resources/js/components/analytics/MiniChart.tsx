import { useMemo } from 'react';

interface MiniChartProps {
  labels: string[];
  datasets: { label: string; data: number[] }[];
  height?: number;
  format?: 'number' | 'percent';
}

export function MiniChart({ labels, datasets, height = 200, format = 'number' }: MiniChartProps) {
  const { max, normalized } = useMemo(() => {
    const allVals = datasets.flatMap((d) => d.data);
    const m = Math.max(...allVals, 1);
    return { max: m, normalized: datasets.map((d) => d.data.map((v) => (v / m) * 100)) };
  }, [datasets]);

  if (!labels.length || !datasets.length) {
    return (
      <div style={{ height }} className="flex items-center justify-center text-sm text-muted-foreground">
        No data available
      </div>
    );
  }

  const colors = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6'];

  return (
    <div style={{ height }} className="relative">
      <svg
        viewBox={`0 0 ${labels.length * 60 + 60} ${height}`}
        className="h-full w-full overflow-visible"
        preserveAspectRatio="xMidYMid meet"
      >
        {datasets.map((dataset, di) => (
          <polyline
            key={di}
            fill="none"
            stroke={colors[di % colors.length]}
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            opacity="0.8"
            points={dataset.data
              .map((val, i) => {
                const x = 30 + i * (labels.length > 1 ? (300 / (labels.length - 1)) : 30);
                const y = height - 20 - ((val / max) * (height - 40));
                return `${x},${y}`;
              })
              .join(' ')}
          />
        ))}
        {datasets.length > 1 && (
          <g transform={`translate(10, 10)`}>
            {datasets.map((d, i) => (
              <g key={i} transform={`translate(0, ${i * 18})`}>
                <circle cx="0" cy="0" r="4" fill={colors[i % colors.length]} />
                <text x="10" y="4" className="text-[10px]" fill="currentColor" opacity="0.7">
                  {d.label}
                </text>
              </g>
            ))}
          </g>
        )}
      </svg>
    </div>
  );
}
