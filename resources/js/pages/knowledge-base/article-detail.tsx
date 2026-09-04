'use client';

import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Link } from 'react-router-dom';
import { useKnowledgeArticle, useDeleteKnowledgeArticle, useArchiveKnowledgeArticle, useRestoreKnowledgeArticle, usePublishKnowledgeArticle, useTrackArticleView, useMarkArticleHelpful, useMarkArticleNotHelpful, useKnowledgeMedia } from '@/hooks/useKnowledge';
import { useKnowledgeStore } from '@/stores/knowledge-store';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
    ArrowLeft, Edit, Eye, Trash2, Archive, RotateCcw, Globe, Share2, MoreHorizontal,
    ThumbsUp, ThumbsDown, ExternalLink, Calendar, Tag, User, Clock,
} from 'lucide-react';
import type { KnowledgeArticle, ArticleStatus, ArticleType, ArticleVisibility } from '@/types/knowledge';

const statusVariant: Record<ArticleStatus, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    published: 'default',
    archived: 'outline',
};

const typeVariant: Record<ArticleType, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    article: 'default',
    guide: 'outline',
    faq: 'secondary',
    glossary: 'destructive',
    documentation: 'outline',
};

const visibilityVariant: Record<ArticleVisibility, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    public: 'default',
    team: 'secondary',
    private: 'outline',
};

const statusLabels: Record<ArticleStatus, string> = {
    draft: 'Draft',
    published: 'Published',
    archived: 'Archived',
};

const typeLabels: Record<ArticleType, string> = {
    article: 'Article',
    guide: 'Guide',
    faq: 'FAQ',
    glossary: 'Glossary',
    documentation: 'Documentation',
};

const visibilityLabels: Record<ArticleVisibility, string> = {
    public: 'Public',
    team: 'Team',
    private: 'Private',
};

function renderContent(content: string | null) {
    if (!content) return <p className="text-muted-foreground">No content</p>;
    
    // Simple markdown-like rendering
    return (
        <div className="prose prose-sm max-w-none dark:prose-invert" dangerouslySetInnerHTML={{
            __html: content
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br />')
        }} />
    );
}

function InfoBadge({ icon: Icon, label, value }: { icon: React.ComponentType<{ className?: string }>; label: string; value: string }) {
    return (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Icon className="h-4 w-4" />
            <span>{label}: </span>
            <span className="font-medium text-foreground">{value}</span>
        </div>
    );
}

export default function KnowledgeArticleDetailPage() {
    const { uuid } = useParams<{ uuid: string }>();
    const navigate = useNavigate();
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [selectedMedia, setSelectedMedia] = useState<string | null>(null);

    const { data: article, isLoading, isError, error } = useKnowledgeArticle(uuid);
    const { data: media } = useKnowledgeMedia(uuid);
    const deleteArticle = useDeleteKnowledgeArticle();
    const archiveArticle = useArchiveKnowledgeArticle();
    const restoreArticle = useRestoreKnowledgeArticle();
    const publishArticle = usePublishKnowledgeArticle();
    const trackView = useTrackArticleView();
    const markHelpful = useMarkArticleHelpful();
    const markNotHelpful = useMarkArticleNotHelpful();

    // Track view when article loads
    if (article && !article.view_tracked) {
        trackView.mutate(article.uuid);
    }

    if (isLoading) {
        return (
            <div className="container mx-auto p-6">
                <div className="space-y-4">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-4 w-96" />
                    <div className="grid gap-4 md:grid-cols-3">
                        {Array.from({ length: 3 }).map((_, i) => (
                            <Card key={i}>
                                <CardContent className="p-4">
                                    <Skeleton className="h-4 w-20" />
                                    <Skeleton className="h-8 w-16 mt-2" />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    <Card>
                        <CardContent className="p-6 space-y-4">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Skeleton key={i} className="h-4 w-full" />
                            ))}
                        </Card>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
            </div>
        );
    }

    if (isError || !article) {
        return (
            <div className="container mx-auto p-6">
                <Card>
                    <CardContent className="flex flex-col items-center py-12">
                        <p className="text-destructive text-lg font-semibold">Failed to load article</p>
                        <p className="text-muted-foreground text-sm">{(error as Error)?.message || 'Article not found'}</p>
                        <Button variant="outline" className="mt-4" onClick={() => navigate('/knowledge')}>
                            Back to Knowledge Base
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    const handleDelete = () => {
        deleteArticle.mutate(article.uuid, {
            onSuccess: () => {
                toast.success('Article deleted');
                navigate('/knowledge');
            },
            onError: () => {
                toast.error('Failed to delete article');
            },
        });
        setDeleteOpen(false);
    };

    const handleArchive = () => {
        archiveArticle.mutate(article.uuid, {
            onSuccess: () => toast.success('Article archived'),
            onError: () => toast.error('Failed to archive article'),
        });
    };

    const handleRestore = () => {
        restoreArticle.mutate(article.uuid, {
            onSuccess: () => toast.success('Article restored'),
            onError: () => toast.error('Failed to restore article'),
        });
    };

    const handlePublish = () => {
        publishArticle.mutate(article.uuid, {
            onSuccess: () => toast.success('Article published'),
            onError: () => toast.error('Failed to publish article'),
        });
    };

    const handleHelpful = () => {
        markHelpful.mutate(article.uuid);
        toast.success('Thanks for your feedback!');
    };

    const handleNotHelpful = () => {
        markNotHelpful.mutate(article.uuid);
        toast.success('Thanks for your feedback!');
    };

    return (
        <div className="container mx-auto p-6">
            <div className="mb-6">
                <Link to="/knowledge" className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="mr-1 h-4 w-4" />
                    Back to Knowledge Base
                </Link>
            </div>

            <div className="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Badge variant={typeVariant[article.article_type]}>
                                            {typeLabels[article.article_type]}
                                        </Badge>
                                        <Badge variant={statusVariant[article.status]}>
                                            {statusLabels[article.status]}
                                        </Badge>
                                        <Badge variant={visibilityVariant[article.visibility]}>
                                            {visibilityLabels[article.visibility]}
                                        </Badge>
                                        {article.is_featured && (
                                            <Badge variant="default" className="bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                Featured
                                            </Badge>
                                        )}
                                    </div>
                                    <h1 className="text-3xl font-bold">{article.title}</h1>
                                    {article.excerpt && (
                                        <p className="mt-2 text-lg text-muted-foreground">{article.excerpt}</p>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button variant="outline" onClick={() => navigate(`/knowledge/articles/${article.uuid}/edit`)}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            {article.status === 'archived' ? (
                                                <DropdownMenuItem onClick={handleRestore}>
                                                    <RotateCcw className="mr-2 h-4 w-4" />
                                                    Restore
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem onClick={handleArchive}>
                                                    <Archive className="mr-2 h-4 w-4" />
                                                    Archive
                                                </DropdownMenuItem>
                                            )}
                                            {article.status === 'draft' && (
                                                <DropdownMenuItem onClick={handlePublish}>
                                                    <Globe className="mr-2 h-4 w-4" />
                                                    Publish
                                                </DropdownMenuItem>
                                            )}
                                            <DropdownMenuItem
                                                className="text-destructive"
                                                onClick={() => setDeleteOpen(true)}
                                            >
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {article.featured_image && (
                                <div className="rounded-lg overflow-hidden">
                                    <img
                                        src={article.featured_image}
                                        alt={article.title}
                                        className="w-full h-auto"
                                    />
                                </div>
                            )}

                            <div className="prose prose-sm max-w-none dark:prose-invert">
                                {renderContent(article.content)}
                            </div>

                            {article.meta_description && (
                                <div className="pt-4 border-t text-sm text-muted-foreground">
                                    <p><strong>SEO Description:</strong> {article.meta_description}</p>
                                </div>
                            )}

                            <div className="pt-4 border-t flex items-center gap-4">
                                <Button variant="outline" size="sm" onClick={handleHelpful}>
                                    <ThumbsUp className="mr-1 h-4 w-4" />
                                    Helpful ({article.helpful_count})
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleNotHelpful}>
                                    <ThumbsDown className="mr-1 h-4 w-4" />
                                    Not Helpful ({article.not_helpful_count})
                                </Button>
                                <span className="text-muted-foreground ml-auto">{article.view_count} views</span>
                            </div>
                        </CardContent>
                    </Card>

                    {media && media.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Attachments</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {media.map((m) => (
                                        <div key={m.uuid} className="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted/50">
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium truncate">{m.original_name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {m.mime_type} • {(m.size / 1024).toFixed(1)} KB
                                                </p>
                                            </div>
                                            <Button variant="outline" size="sm" asChild>
                                                <a href={m.url || `/api/media/${m.path}`} target="_blank" rel="noopener noreferrer">
                                                    <ExternalLink className="mr-1 h-4 w-4" />
                                                    Download
                                                </a>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InfoBadge icon={Calendar} label="Created" value={format(new Date(article.created_at), 'MMM d, yyyy')} />
                            <InfoBadge icon={Clock} label="Updated" value={format(new Date(article.updated_at), 'MMM d, yyyy')} />
                            {article.published_at && (
                                <InfoBadge icon={Globe} label="Published" value={format(new Date(article.published_at), 'MMM d, yyyy')} />
                            )}
                            {article.category && (
                                <InfoBadge icon={Tag} label="Category" value={article.category.name} />
                            )}
                            {article.author && (
                                <InfoBadge icon={User} label="Author" value={article.author.name} />
                            )}
                            <div className="pt-4 border-t">
                                <p className="text-sm text-muted-foreground">Article ID: {article.id}</p>
                                <p className="text-sm text-muted-foreground">UUID: {article.uuid}</p>
                            </div>
                        </CardContent>
                    </Card>

                    {article.tags && article.tags.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Tags</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-2">
                                    {article.tags.map((tag) => (
                                        <Badge key={tag.id} variant="secondary" className="gap-1">
                                            {tag.color && (
                                                <span
                                                    className="h-2 w-2 rounded-full"
                                                    style={{ backgroundColor: tag.color }}
                                                />
                                            )}
                                            {tag.name}
                                        </Badge>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Article</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{article.title}"? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={deleteArticle.isPending}>
                            {deleteArticle.isPending ? <Spinner className="mr-2" /> : null}
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {selectedMedia && (
                <Dialog open={!!selectedMedia} onOpenChange={(open) => { if (!open) setSelectedMedia(null); }}>
                    <DialogContent className="max-w-4xl">
                        <img src={selectedMedia} alt="Preview" className="max-h-[70vh] w-auto" />
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}