import { Link, Outlet } from 'react-router-dom';
import { Building2, Brain, MessageCircle, Shield } from 'lucide-react';

export default function AuthLayout() {
    return (
        <div className="flex min-h-screen">
            <div className="hidden w-1/2 bg-[var(--sidebar)] lg:flex lg:flex-col lg:items-center lg:justify-center lg:p-12">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--sidebar-primary)]">
                        <Building2 className="h-8 w-8 text-white" />
                    </div>
                    <h1 className="text-3xl font-bold text-white mb-3">
                        Business Assistant
                    </h1>
                    <p className="text-slate-400 text-lg mb-10">
                        Automate your WhatsApp conversations with AI-powered customer support.
                    </p>

                    <div className="space-y-4 text-left">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                <MessageCircle className="h-5 w-5 text-emerald-400" />
                            </div>
                            <div>
                                <p className="font-medium text-white text-sm">
                                    Smart WhatsApp Integration
                                </p>
                                <p className="text-sm text-slate-400">
                                    Connect your WhatsApp Business account and start automating conversations instantly.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-500/10">
                                <Brain className="h-5 w-5 text-teal-400" />
                            </div>
                            <div>
                                <p className="font-medium text-white text-sm">
                                    AI-Powered Responses
                                </p>
                                <p className="text-sm text-slate-400">
                                    Intelligent auto-replies that understand your business and handle customer queries 24/7.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10">
                                <Shield className="h-5 w-5 text-cyan-400" />
                            </div>
                            <div>
                                <p className="font-medium text-white text-sm">
                                    Enterprise-Grade Security
                                </p>
                                <p className="text-sm text-slate-400">
                                    Your data is encrypted and protected with bank-level security standards.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex w-full flex-col lg:w-1/2">
                <header className="flex items-center justify-between px-6 py-4 lg:px-8">
                    <Link to="/" className="flex items-center gap-2 lg:hidden">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--primary)]">
                            <Building2 className="h-4 w-4 text-white" />
                        </div>
                        <span className="text-sm font-semibold">Business Assistant</span>
                    </Link>
                    <Link
                        to="/login"
                        className="ml-auto text-sm text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Already have an account? Sign in
                    </Link>
                </header>

                <main className="flex flex-1 items-center justify-center px-6 py-12">
                    <div className="w-full max-w-md">
                        <Outlet />
                    </div>
                </main>

                <footer className="px-6 py-4 text-center text-xs text-muted-foreground lg:px-8">
                    &copy; {new Date().getFullYear()} Business WhatsApp AI Assistant. All
                    rights reserved.
                </footer>
            </div>
        </div>
    );
}
