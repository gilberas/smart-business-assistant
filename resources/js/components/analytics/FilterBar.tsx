import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface FilterBarProps {
  groupBy?: string;
  onGroupByChange?: (value: string) => void;
  mode?: string;
  onModeChange?: (value: string) => void;
  children?: React.ReactNode;
}

export function FilterBar({ groupBy, onGroupByChange, mode, onModeChange, children }: FilterBarProps) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      {onGroupByChange && (
        <Select value={groupBy || 'day'} onValueChange={onGroupByChange}>
          <SelectTrigger className="w-[130px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="day">Daily</SelectItem>
            <SelectItem value="week">Weekly</SelectItem>
            <SelectItem value="month">Monthly</SelectItem>
          </SelectContent>
        </Select>
      )}
      {onModeChange && (
        <Select value={mode || 'all'} onValueChange={onModeChange}>
          <SelectTrigger className="w-[130px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Modes</SelectItem>
            <SelectItem value="ai">AI Only</SelectItem>
            <SelectItem value="human">Human Only</SelectItem>
          </SelectContent>
        </Select>
      )}
      {children}
    </div>
  );
}
