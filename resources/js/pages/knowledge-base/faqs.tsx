'use client';

import { useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    useKnowledgeFaqs,
    useKnowledgeCategories,
    useCreateKnowledgeFaq,
    useUpdateKnowledgeFaq,
    useDeleteKnowledgeFaq,
} from '@/hooks/useKnowledge';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardHeader, CardTitle, CardDescription,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import {
    Search, MoreHorizontal, Plus, Pencil, Trash2, HelpCircle, Globe, EyeOff,
} from 'lucide-react';
import type { KnowledgeFaq } from '@/types/knowledge';

type FaqFormState = {
    question: string;
    answer: string;
    category_id: string | undefined;
    is_published: boolean;
    sort_order: string;
};

const emptyForm: FaqFormState = {
    question: '',
    answer: '',
    category_id: undefined,
    is_published: true,
    sort_order: '0',
};

export default function FaqLibraryPage() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<KnowledgeFaq | null>(null);
    const [form, setForm] = useState<FaqFormState>(emptyForm);
    const [deleteTarget, setDeleteTarget] = useState<KnowledgeFaq | null>(null);

    const { data, isLoading, isError, refetch } = useKnowledgeFaqs(
        search ? { search } : undefined,
    );
    const { data: categories } = useKnowledgeCategories({ is_active: true });

    const createMutation = useCreateKnowledgeFaq();
    const updateMutation = useUpdateKnowledgeFaq(editing?.uuid);
    const deleteMutation = useDeleteKnowledgeFaq();

    const faqs: KnowledgeFaq[] = data?.data ?? [];
    const isSaving = createMutation.isPending || updateMutation.isPending;

    const openCreate = useCallback(() => {
        setEditing(null);
        setForm(emptyForm);
        setDialogOpen(true);
    }, []);

    const openEdit = useCallback((faq: KnowledgeFaq) => {
        setEditing(faq);
        setForm({
            question: faq.question,
            answer: faq.answer ?? '',
            category_id: faq.category_id ? String(faq.category_id) : undefined,
            is_published: faq.is_published,
            sort_order: String(faq.sort_order ?? 0),
        });
        setDialogOpen(true);
    }, []);

    const handleSave = useCallback(async () => {
        if (!form.question.trim()) {
            toast.error('Question is required');
            return;
        }

        const payload = {
            question: form.question.trim(),
            answer: form.answer.trim() || undefined,
            category_id: form.category_id ? Number(form.category_id) : undefined,
            is_published: form.is_published,
            sort_order: Number(form.sort_order ?? 0),
        };

        try {
            if (editing) {
                await updateMutation.mutateAsync(payload);
                toast.success('FAQ updated');
            } else {
                await createMutation.mutateAsync(payload);
                toast.success('FAQ created');
            }
            setDialogOpen(false);
            refetch();
        } catch (e) {
            toast.error('Failed to save FAQ');
        }
    }, [form, editing, createMutation, updateMutation, refetch]);

    const handleDelete = useCallback(async () => {
        if (!deleteTarget) return;
        try {
            await deleteMutation.mutateAsync(deleteTarget.uuid);
            toast.success('FAQ deleted');
            setDeleteTarget(null);
            refetch();
        } catch (e) {
            toast.error('Failed to delete FAQ');
        }
    }, [deleteTarget, deleteMutation, refetch]);

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">FAQ Library</h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Curated questions and answers your AI assistant can draw on to answer
                        customers. Published FAQs are indexed by the RAG engine and surfaced when
                        they are relevant to a conversation.
                    </p>
                </div>
                <Button onClick={openCreate} className="shrink-0">
                    <Plus className="mr-2 h-4 w-4" />
                    New FAQ
                </Button>
            </div>

            <Card>
                <CardHeader className="gap-3">
                    <div className="flex items-center justify-between gap-3">
                        <CardTitle className="text-base">All FAQs</CardTitle>
                        <div className="relative w-full max-w-xs">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search questions..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-8"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="space-y-3">
                            {[0, 1, 2, 3].map((i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : isError ? (
                        <div className="py-10 text-center text-sm text-muted-foreground">
                            Failed to load FAQs.{' '}
                            <button className="underline" onClick={() => refetch()}>
                                Retry
                            </button>
                        </div>
                    ) : faqs.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 py-12 text-center">
                            <HelpCircle className="h-8 w-8 text-muted-foreground" />
                            <p className="text-sm font-medium">No FAQs yet</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Add common questions and answers so your AI assistant can answer
                                them automatically. Try creating your first one.
                            </p>
                            <Button variant="outline" className="mt-2" onClick={openCreate}>
                                <Plus className="mr-2 h-4 w-4" />
                                New FAQ
                            </Button>
                        </div>
                    ) : (
                        <div className="divide-y rounded-md border">
                            {faqs.map((faq) => (
                                <div
                                    key={faq.id}
                                    className="flex items-center justify-between gap-3 px-4 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {faq.question}
                                        </p>
                                        <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                            {faq.answer
                                                ? faq.answer
                                                : 'No answer provided'}
                                        </p>
                                        <div className="mt-1.5 flex items-center gap-2">
                                            {faq.category?.name && (
                                                <Badge variant="secondary">
                                                    {faq.category.name}
                                                </Badge>
                                            )}
                                            {faq.is_published ? (
                                                <Badge variant="default" className="gap-1">
                                                    <Globe className="h-3 w-3" />
                                                    Published
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="gap-1">
                                                    <EyeOff className="h-3 w-3" />
                                                    Draft
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem onClick={() => openEdit(faq)}>
                                                <Pencil className="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                className="text-destructive"
                                                onClick={() => setDeleteTarget(faq)}
                                            >
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Edit FAQ' : 'New FAQ'}
                        </DialogTitle>
                        <DialogDescription>
                            Published FAQs are indexed by the AI assistant and shown to customers
                            when relevant.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium">Question</label>
                            <Input
                                value={form.question}
                                onChange={(e) =>
                                    setForm((f) => ({ ...f, question: e.target.value }))
                                }
                                placeholder="e.g. What are your opening hours?"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium">Answer</label>
                            <Textarea
                                value={form.answer}
                                onChange={(e) =>
                                    setForm((f) => ({ ...f, answer: e.target.value }))
                                }
                                rows={5}
                                placeholder="The answer your assistant should give..."
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <label className="text-sm font-medium">Category</label>
                                <Select
                                    value={form.category_id ?? 'none'}
                                    onValueChange={(v) =>
                                        setForm((f) => ({
                                            ...f,
                                            category_id: v === 'none' ? undefined : v,
                                        }))
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Uncategorized" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Uncategorized</SelectItem>
                                        {categories?.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <label className="text-sm font-medium">Sort order</label>
                                <Input
                                    type="number"
                                    value={form.sort_order}
                                    onChange={(e) =>
                                        setForm((f) => ({ ...f, sort_order: e.target.value }))
                                    }
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={form.is_published}
                                onCheckedChange={(v) =>
                                    setForm((f) => ({ ...f, is_published: v }))
                                }
                            />
                            <span className="text-sm">
                                {form.is_published
                                    ? 'Published (visible to AI assistant)'
                                    : 'Draft (not indexed)'}
                            </span>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleSave} disabled={isSaving}>
                            {isSaving && <Spinner className="mr-2 h-4 w-4" />}
                            {editing ? 'Save changes' : 'Create FAQ'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleteTarget} onOpenChange={(o) => !o && setDeleteTarget(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Delete FAQ</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete &ldquo;{deleteTarget?.question}&rdquo;?
                            This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleteMutation.isPending}
                        >
                            {deleteMutation.isPending && (
                                <Spinner className="mr-2 h-4 w-4" />
                            )}
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
