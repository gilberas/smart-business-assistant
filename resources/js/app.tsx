import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { ThemeProvider } from '@/providers/ThemeProvider';

export default function App({ children }: { children: React.ReactNode }) {
    return (
        <ThemeProvider>
            <TooltipProvider delayDuration={0}>
                {children}
                <Toaster />
            </TooltipProvider>
        </ThemeProvider>
    );
}
