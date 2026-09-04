'use client';

import { useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    useKnowledgeArticles,
    useKnowledgeArticleStats,
    useDeleteKnowledgeArticle,
    useArchiveKnowledgeArticle,
    useRestoreKnowledgeArticle,
    usePublishKnowledgeArticle,
} from '@/hooks/useKnowledge';
import { useKnowledgeStore } from '@/stores/knowledge-store';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { format } from '@/lib/date-utils';
import {
    Search, MoreHorizontal, Plus, Upload, Download, Eye, Pencil, Archive, Trash2, RotateCcw, Globe,
} from 'lucide-react';
import type {
    KnowledgeArticleFilters,
    KnowledgeArticleList,
    ArticleStatus,
    ArticleType,
    ArticleVisibility,
    KnowledgeArticleStats,
} from '@/types/knowledge';

const statusOptions: { value: ArticleStatus | 'all'; label: string }[] = [
    { value: 'all', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'archived', label: 'Archived' },
];

const typeOptions: { value: ArticleType | 'all'; label: string }[] = [
    { value: 'all', label: 'All Types' },
    { value: 'article', label: 'Article' },
    { value: 'guide', label: 'Guide' },
    { value: 'faq', label: 'FAQ' },
    { value: 'glossary', label: 'Glossary' },
    { value: 'documentation', label: 'Documentation' },
];

const visibilityOptions: { value: ArticleVisibility | 'all'; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'public', label: 'Public' },
    { value: 'team', label: 'Team' },
    { value: 'private', label: 'Private' },
];

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    published: 'default',
    archived: 'secondary',
};

const typeVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    article: 'default',
    guide: 'secondary',
    faq: 'outline',
    glossary: 'secondary',
    documentation: 'destructive',
};

const visibilityVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    public: 'default',
    team: 'secondary',
    private: 'outline',
};

function StatsCards() {
    const { data: stats, isLoading } = useKnowledgeArticleStats();

    if (isLoading || !stats) {
        return (
            <div className="grid gap-4 md:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Card key={i}>
                        <CardHeader className="pb-2">
                            <Skeleton className="h-4 w-20" />
                        </CardHeader>
                        <CardContent>
                            <Skeleton className="h-8 w-16" />
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Total</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold">{stats.total}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Published</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold text-green-600">{stats.published}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Drafts</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold text-muted-foreground">{stats.drafts}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Archived</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold text-muted-foreground">{stats.archived}</p>
                </CardContent>
            </Card>
        </div>
    );
}

function FilterBar({
    filters,
    onFiltersChange,
}: {
    filters: KnowledgeArticleFilters;
    onFiltersChange: (filters: KnowledgeArticleFilters) => void;
}) {
    const setFilter = <K extends keyof KnowledgeArticleFilters>(key: K, value: KnowledgeArticleFilters[K]) => {
        onFiltersChange({ ...filters, [key]: value || undefined });
    };

    return (
        <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Status</label>
                <Select
                    value={filters.status as string || 'all'}
                    onValueChange={(v) => setFilter('status', v === 'all' ? undefined : (v as ArticleStatus))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statusOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Type</label>
                <Select
                    value={filters.article_type as string || 'all'}
                    onValueChange={(v) => setFilter('article_type', v === 'all' ? undefined : (v as ArticleType))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Type" />
                    </SelectTrigger>
                    <SelectContent>
                        {typeOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Visibility</label>
                <Select
                    value={filters.visibility as string || 'all'}
                    onValueChange={(v) => setFilter('visibility', v === 'all' ? undefined : (v as ArticleVisibility))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Visibility" />
                    </SelectTrigger>
                    <SelectContent>
                        {visibilityOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Search</label>
                <div className="relative">
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Title, content..."
                        value={filters.search || ''}
                        onChange={(e) => setFilter('search', e.target.value || undefined)}
                        className="w-56 pl-8"
                    />
                </div>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">From</label>
                <Input
                    type="date"
                    value={filters.date_from || ''}
                    onChange={(e) => setFilter('date_from', e.target.value || undefined)}
                    className="w-40"
                />
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">To</label>
                <Input
                    type="date"
                    value={filters.date_to || ''}
                    onChange={(e) => setFilter('date_to', e.target.value || undefined)}
                    className="w-40"
                />
            </div>
            <Button
                variant="ghost"
                size="sm"
                onClick={() => onFiltersChange({})}
                className="mb-0.5"
            >
                Clear
            </Button>
        </div>
    );
}

export default function KnowledgeArticleListPage() {
    const navigate = useNavigate();
    const { filters: storeFilters, setFilters, resetFilters } = useKnowledgeStore();
    const [page, setPage] = useState(1);
    const [localFilters, setLocalFilters] = useState<KnowledgeArticleFilters>({});
    const [deleteUuid, setDeleteUuid] = useState<string | null>(null);

    const effectiveFilters = { ...localFilters, ...storeFilters, page, per_page: 15 };
    const { data, isLoading, isError, error } = useKnowledgeArticles(effectiveFilters);
    const deleteArticle = useDeleteKnowledgeArticle();
    const archiveArticle = useArchiveKnowledgeArticle();
    const restoreArticle = useRestoreKnowledgeArticle();
    const publishArticle = usePublishKnowledgeArticle();

    const handleDelete = useCallback(() => {
        if (!deleteUuid) return;
        deleteArticle.mutate(deleteUuid, {
            onSuccess: () => {
                toast.success('Article deleted');
                setDeleteUuid(null);
            },
            onError: () => {
                toast.error('Failed to delete article');
            },
        });
    }, [deleteArticle, deleteUuid]);

    const handleArchive = useCallback((uuid: string) => {
        archiveArticle.mutate(uuid, {
            onSuccess: () => toast.success('Article archived'),
            onError: () => toast.error('Failed to archive article'),
        });
    }, [archiveArticle]);

    const handleRestore = useCallback((uuid: string) => {
        restoreArticle.mutate(uuid, {
            onSuccess: () => toast.success('Article restored'),
            onError: () => toast.error('Failed to restore article'),
        });
    }, [restoreArticle]);

    const handlePublish = useCallback((uuid: string) => {
        publishArticle.mutate(uuid, {
            onSuccess: () => toast.success('Article published'),
            onError: () => toast.error('Failed to publish article'),
        });
    }, [publishArticle]);

    if (isError) {
        return (
            <div className="container mx-auto p-6">
                <Card>
                    <CardContent className="flex flex-col items-center py-12">
                        <p className="text-destructive text-lg font-semibold">Failed to load articles</p>
                        <p className="text-muted-foreground text-sm">{(error as Error)?.message || 'An unexpected error occurred'}</p>
                        <Button variant="outline" className="mt-4" onClick={() => window.location.reload()}>
                            Retry
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold">Knowledge Base</h1>
                    <p className="text-muted-foreground">Manage your articles, FAQs, and business information</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button onClick={() => navigate('/knowledge/articles/create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Create Article
                    </Button>
                </div>
            </div>

            <StatsCards />

            <FilterBar
                filters={{ ...localFilters, ...storeFilters }}
                onFiltersChange={(f) => {
                    setLocalFilters(f);
                    setPage(1);
                }}
            />

            <Card>
                <CardContent className="p-0">
                    {isLoading ? (
                        <div className="space-y-3 p-4">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">Title</th>
                                        <th className="px-4 py-3 font-medium">Type</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">Visibility</th>
                                        <th className="px-4 py-3 font-medium">Category</th>
                                        <th className="px-4 py-3 font-medium">Views</th>
                                        <th className="px-4 py-3 font-medium">Published</th>
                                        <th className="px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data?.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-12 text-center text-muted-foreground">
                                                No articles found
                                            </td>
                                        </tr>
                                    ) : (
                                        data?.data.map((article: KnowledgeArticleList) => (
                                            <tr key={article.uuid} className="border-b last:border-0 hover:bg-muted/50">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        to={`/knowledge/articles/${article.uuid}`}
                                                        className="font-medium text-primary hover:underline"
                                                    >
                                                        {article.title}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={typeVariant[article.article_type] || 'outline'}>
                                                        {article.article_type}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={statusVariant[article.status] || 'outline'}>
                                                        {article.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={visibilityVariant[article.visibility] || 'outline'}>
                                                        {article.visibility}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {article.category?.name || '-'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {article.view_count.toLocaleString()}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {article.published_at
                                                        ? format(new Date(article.published_at), 'MMM d, yyyy')
                                                        : '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem onClick={() => navigate(`/knowledge/articles/${article.uuid}`)}>
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                View
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => navigate(`/knowledge/articles/${article.uuid}/edit`)}>
                                                                <Pencil className="mr-2 h-4 w-4" />
                                                                Edit
                                                            </DropdownMenuItem>
                                                            {article.status === 'published' ? (
                                                                <DropdownMenuItem onClick={() => handleArchive(article.uuid)}>
                                                                    <Archive className="mr-2 h-4 w-4" />
                                                                    Archive
                                                                </DropdownMenuItem>
                                                            ) : article.status === 'archived' ? (
                                                                <DropdownMenuItem onClick={() => handleRestore(article.uuid)}>
                                                                    <RotateCcw className="mr-2 h-4 w-4" />
                                                                    Restore
                                                                </DropdownMenuItem>
                                                            ) : (
                                                                <DropdownMenuItem onClick={() => handlePublish(article.uuid)}>
                                                                    <Globe className="mr-2 h-4 w-4" />
                                                                    Publish
                                                                </DropdownMenuItem>
                                                            )}
                                                            <DropdownMenuItem
                                                                className="text-destructive"
                                                                onClick={() => setDeleteUuid(article.uuid)}
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Delete
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {data?.meta && data.meta.last_page > 1 && (
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        Showing {data.meta.from}–{data.meta.to} of {data.meta.total}
                    </p>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page <= 1}
                            onClick={() => setPage(page - 1)}
                        >
                            Previous
                        </Button>
                        {Array.from({ length: data.meta.last_page }, (_, i) => i + 1)
                            .filter((p) => p === 1 || p === data.meta.last_page || Math.abs(p - page) <= 2)
                            .map((p, idx, arr) => (
                                <span key={p} className="flex items-center">
                                    {idx > 0 && arr[idx - 1] !== p - 1 && (
                                        <span className="px-1 text-muted-foreground">...</span>
                                    )}
                                    <Button
                                        variant={p === page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setPage(p)}
                                        className="min-w-9"
                                    >
                                        {p}
                                    </Button>
                                </span>
                            ))}
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page >= data.meta.last_page}
                            onClick={() => setPage(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}

            <Dialog open={!!deleteUuid} onOpenChange={(open) => !open && setDeleteUuid(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Article</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this article? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteUuid(null)}>Cancel</Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleteArticle.isPending}
                        >
                            {deleteArticle.isPending ? <Spinner className="mr-2" /> : null}
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}