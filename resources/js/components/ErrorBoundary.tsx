import { Component, type ErrorInfo, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('ErrorBoundary caught:', error, errorInfo);
    }

    handleReload = () => {
        window.location.reload();
    };

    handleReset = () => {
        this.setState({ hasError: false, error: null });
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-screen flex-col items-center justify-center gap-4 px-4 text-center">
                    <div className="space-y-2">
                        <h2 className="text-xl font-semibold">Something went wrong</h2>
                        <p className="max-w-md text-sm text-muted-foreground">
                            An unexpected error occurred. You can try reloading the page or
                            continue from where you left off.
                        </p>
                        {this.state.error && (
                            <p className="max-w-md text-xs text-muted-foreground font-mono mt-2">
                                {this.state.error.message}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button onClick={this.handleReset}>Try again</Button>
                        <Button variant="outline" onClick={this.handleReload}>
                            Reload page
                        </Button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
