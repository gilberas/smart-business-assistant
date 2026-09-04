'use client';
import { useState } from 'react';
import { usePrompts, useCreatePrompt, useUpdatePrompt, useDeletePrompt, useTestPrompt } from '@/hooks/useAI';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { Plus, Play, History, Copy, Pencil, Trash2 } from 'lucide-react';
import type { PromptTemplate } from '@/types/ai';
import { useTheme } from '@/hooks/useTheme';

const PROMPT_TYPES = [
    { value: 'system', label: 'System' },
    { value: 'business', label: 'Business' },
    { value: 'conversation', label: 'Conversation' },
    { value: 'knowledge', label: 'Knowledge' },
    { value: 'classification', label: 'Classification' },
    { value: 'moderation', label: 'Moderation' },
    { value: 'summarization', label: 'Summarization' },
];

export default function PromptEditorPage() {
    const [typeFilter, setTypeFilter] = useState<string>('');
    const { data: promptsData, isLoading } = usePrompts(typeFilter ? { type: typeFilter } : undefined);
    const createPrompt = useCreatePrompt();
    const updatePrompt = useUpdatePrompt();
    const deletePrompt = useDeletePrompt();
    const testPrompt = useTestPrompt();

    const [editingPrompt, setEditingPrompt] = useState<PromptTemplate | null>(null);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [testVars, setTestVars] = useState('');
    const [testResult, setTestResult] = useState<string | null>(null);
    const { theme } = useTheme();

    const handleCreate = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = new FormData(e.currentTarget);
        try {
            await createPrompt.mutateAsync({
                name: form.get('name') as string,
                type: form.get('type') as string,
                content: form.get('content') as string,
                description: form.get('description') as string,
            });
            toast.success('Prompt template created');
            setIsCreateOpen(false);
        } catch {
            toast.error('Failed to create prompt');
        }
    };

    const handleUpdate = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!editingPrompt) return;
        const form = new FormData(e.currentTarget);
        try {
            await updatePrompt.mutateAsync({
                id: editingPrompt.id,
                name: form.get('name') as string,
                content: form.get('content') as string,
                description: form.get('description') as string,
                changelog: form.get('changelog') as string,
            });
            toast.success('Prompt template updated');
            setIsEditOpen(false);
        } catch {
            toast.error('Failed to update prompt');
        }
    };

    const handleTest = async (prompt: PromptTemplate) => {
        try {
            const vars = testVars ? JSON.parse(testVars) : {};
            const result = await testPrompt.mutateAsync({ id: prompt.id, variables: vars });
            setTestResult(result.data.rendered);
        } catch {
            toast.error('Failed to test prompt');
        }
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        toast.success('Copied to clipboard');
    };

    const extractVariables = (content: string): string[] => {
        const matches = content.match(/\{\{?\s*(\w+)\s*\}?\}/g);
        return matches ? [...new Set(matches.map((m) => m.replace(/[\{\}\s]/g, '')))] : [];
    };

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold">Prompt Editor</h1>
                    <p className="text-muted-foreground">Manage AI prompt templates with version control</p>
                </div>
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button>
                            <Plus className="mr-2 size-4" />
                            New Prompt
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Create Prompt Template</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div className="space-y-2">
                                <Label>Name</Label>
                                <Input name="name" required placeholder="e.g., Support Response" />
                            </div>
                            <div className="space-y-2">
                                <Label>Type</Label>
                                <Select name="type" defaultValue="system">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PROMPT_TYPES.map((t) => (
                                            <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Content</Label>
                                <Textarea name="content" required className="min-h-[200px] font-mono text-sm" placeholder="Enter prompt template with {{variables}}" />
                            </div>
                            <div className="space-y-2">
                                <Label>Description</Label>
                                <Input name="description" placeholder="Optional description" />
                            </div>
                            <Button type="submit" className="w-full">Create Template</Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="flex gap-2">
                <Select value={typeFilter} onValueChange={setTypeFilter}>
                    <SelectTrigger className="w-[200px]">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Types</SelectItem>
                        {PROMPT_TYPES.map((t) => (
                            <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                {promptsData?.data?.map((prompt: PromptTemplate) => {
                    const variables = extractVariables(prompt.content);
                    return (
                        <Card key={prompt.id}>
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <CardTitle className="text-lg">{prompt.name}</CardTitle>
                                        <Badge variant="secondary" className="mt-1">
                                            {PROMPT_TYPES.find((t) => t.value === prompt.type)?.label ?? prompt.type}
                                        </Badge>
                                    </div>
                                    <span className="text-xs text-muted-foreground">v{prompt.version}</span>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <pre className={`max-h-[200px] overflow-auto rounded-lg p-3 text-xs ${theme === 'dark' ? 'bg-gray-900 text-green-400' : 'bg-gray-100 text-gray-800'}`}>
                                    <code>{prompt.content}</code>
                                </pre>
                                {variables.length > 0 && (
                                    <div className="flex flex-wrap gap-1">
                                        {variables.map((v) => (
                                            <Badge key={v} variant="outline" className="text-xs">{`{{${v}}}`}</Badge>
                                        ))}
                                    </div>
                                )}
                                <div className="flex gap-2">
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="outline" size="sm" onClick={() => { setEditingPrompt(prompt); setTestResult(null); setTestVars(''); }}>
                                                <Play className="mr-1 size-3" /> Test
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-2xl">
                                            <DialogHeader>
                                                <DialogTitle>Test: {prompt.name}</DialogTitle>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label>Variables (JSON)</Label>
                                                    <Textarea
                                                        value={testVars}
                                                        onChange={(e) => setTestVars(e.target.value)}
                                                        className="font-mono text-sm"
                                                        placeholder='{"name": "John", "business_name": "Acme"}'
                                                    />
                                                </div>
                                                <Button onClick={() => handleTest(prompt)} disabled={testPrompt.isPending}>
                                                    {testPrompt.isPending ? 'Rendering...' : 'Render Prompt'}
                                                </Button>
                                                {testResult && (
                                                    <div className="space-y-2">
                                                        <div className="flex items-center justify-between">
                                                            <Label>Result</Label>
                                                            <Button variant="ghost" size="sm" onClick={() => copyToClipboard(testResult)}>
                                                                <Copy className="size-3" />
                                                            </Button>
                                                        </div>
                                                        <pre className={`max-h-[300px] overflow-auto rounded-lg p-3 text-sm ${theme === 'dark' ? 'bg-gray-900 text-green-400' : 'bg-gray-100 text-gray-800'}`}>
                                                            <code>{testResult}</code>
                                                        </pre>
                                                    </div>
                                                )}
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                    <Button variant="outline" size="sm" onClick={() => { setEditingPrompt(prompt); setIsEditOpen(true); }}>
                                        <Pencil className="mr-1 size-3" /> Edit
                                    </Button>
                                    <Button variant="outline" size="sm" onClick={() => copyToClipboard(prompt.content)}>
                                        <Copy className="mr-1 size-3" /> Copy
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Edit: {editingPrompt?.name}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Name</Label>
                            <Input name="name" defaultValue={editingPrompt?.name} />
                        </div>
                        <div className="space-y-2">
                            <Label>Content</Label>
                            <Textarea name="content" className="min-h-[200px] font-mono text-sm" defaultValue={editingPrompt?.content} />
                        </div>
                        <div className="space-y-2">
                            <Label>Change Log</Label>
                            <Input name="changelog" placeholder="Describe what changed" />
                        </div>
                        <Button type="submit" className="w-full">Save Changes</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
