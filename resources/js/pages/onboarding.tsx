import { Sparkles } from 'lucide-react';
import { Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { OnboardingWizard } from '@/components/features/business/OnboardingWizard';
import { Spinner } from '@/components/ui/spinner';
import { useBusinesses } from '@/hooks/useBusiness';
import { useBusinessStore } from '@/stores/business-store';

export default function OnboardingPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const { isLoading, isSuccess } = useBusinesses();
    const businesses = useBusinessStore((state) => state.businesses);

    const businessIdParam = searchParams.get('business');

    const resumeBusiness = businessIdParam
        ? businesses.find((b) => String(b.id) === businessIdParam && !b.onboarding_completed_at)
        : undefined;

    if (isLoading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <Spinner className="h-8 w-8" />
            </div>
        );
    }

    if (resumeBusiness) {
        return (
            <OnboardingShell>
                <div className="mx-auto mb-8 text-center">
                    <h1 className="text-2xl font-bold tracking-tight">Finish setting up</h1>
                    <p className="mt-1 text-muted-foreground">
                        Complete the last steps for {resumeBusiness.name} to start automating
                        WhatsApp conversations with AI.
                    </p>
                </div>
                <OnboardingWizard business={resumeBusiness} onComplete={() => navigate('/')} />
            </OnboardingShell>
        );
    }

    if (isSuccess && businesses.length > 0) {
        return <Navigate to="/" replace />;
    }

    return (
        <OnboardingShell>
            <div className="mx-auto mb-8 text-center">
                <h1 className="text-2xl font-bold tracking-tight">Welcome aboard</h1>
                <p className="mt-1 text-muted-foreground">
                    Create your business to start automating WhatsApp conversations with AI.
                </p>
            </div>
            <OnboardingWizard onComplete={() => navigate('/')} />
        </OnboardingShell>
    );
}

function OnboardingShell({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-muted/30">
            <header className="border-b bg-background">
                <div className="container mx-auto flex h-14 items-center px-4">
                    <span className="flex items-center gap-2 text-lg font-semibold">
                        <Sparkles className="h-5 w-5" />
                        Business Assistant
                    </span>
                </div>
            </header>
            <main className="flex flex-1 flex-col items-center justify-center px-4 py-10">
                {children}
            </main>
            <footer className="border-t bg-background py-4">
                <div className="container mx-auto text-center text-sm text-muted-foreground">
                    &copy; {new Date().getFullYear()} Business WhatsApp AI Assistant. All rights
                    reserved.
                </div>
            </footer>
        </div>
    );
}
