interface DoughnutChartProps {
  labels: string[];
  data: number[];
  height?: number;
}

const colors = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

export function DoughnutChart({ labels, data, height = 200 }: DoughnutChartProps) {
  const total = data.reduce((a, b) => a + b, 0) || 1;
  const cx = 100;
  const cy = 100;
  const r = 70;
  const circumference = 2 * Math.PI * r;
  let offset = 0;

  if (!data.length) {
    return (
      <div style={{ height }} className="flex items-center justify-center text-sm text-muted-foreground">
        No data available
      </div>
    );
  }

  return (
    <div className="flex items-center gap-4" style={{ height }}>
      <svg width="200" height="200" viewBox="0 0 200 200">
        {data.map((value, i) => {
          const segment = (value / total) * circumference;
          const dash = `${segment} ${circumference - segment}`;
          const strokeDashoffset = -offset;
          offset += segment;
          return (
            <circle
              key={i}
              cx={cx}
              cy={cy}
              r={r}
              fill="none"
              stroke={colors[i % colors.length]}
              strokeWidth="20"
              strokeDasharray={dash}
              strokeDashoffset={strokeDashoffset}
              transform={`rotate(-90 ${cx} ${cy})`}
              opacity="0.85"
            />
          );
        })}
        <text x={cx} y={cy - 6} textAnchor="middle" className="text-lg font-bold" fill="currentColor">
          {total}
        </text>
        <text x={cx} y={cy + 14} textAnchor="middle" className="text-[10px]" fill="currentColor" opacity="0.6">
          Total
        </text>
      </svg>
      <div className="space-y-1.5">
        {labels.map((label, i) => (
          <div key={i} className="flex items-center gap-2 text-xs">
            <span
              className="inline-block h-2.5 w-2.5 rounded-full shrink-0"
              style={{ backgroundColor: colors[i % colors.length] }}
            />
            <span className="text-muted-foreground truncate max-w-[120px]">{label}</span>
            <span className="font-medium ml-auto">
              {total > 0 ? Math.round((data[i] / total) * 100) : 0}%
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}
