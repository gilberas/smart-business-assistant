'use client';

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useKnowledgeArticle, useUpdateKnowledgeArticle, useKnowledgeCategories, useKnowledgeTags } from '@/hooks/useKnowledge';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { ArrowLeft, Save } from 'lucide-react';

const updateArticleSchema = z.object({
    title: z.string().min(1, 'Title is required').max(255),
    slug: z.string().max(255).optional(),
    excerpt: z.string().optional(),
    content: z.string().optional(),
    category_id: z.number().nullable().optional(),
    article_type: z.enum(['article', 'guide', 'faq', 'glossary', 'documentation']).default('article'),
    status: z.enum(['draft', 'published', 'archived']).default('draft'),
    visibility: z.enum(['public', 'team', 'private']).default('team'),
    featured_image: z.string().url().optional().or(z.literal('')),
    meta_title: z.string().max(255).optional(),
    meta_description: z.string().max(500).optional(),
    meta_keywords: z.string().max(500).optional(),
    is_featured: z.boolean().default(false),
    published_at: z.string().optional(),
    tag_ids: z.array(z.number()).optional(),
    tags: z.array(z.string().max(100)).optional(),
});

type UpdateArticleForm = z.infer<typeof updateArticleSchema>;

const typeOptions = [
    { value: 'article', label: 'Article' },
    { value: 'guide', label: 'Guide' },
    { value: 'faq', label: 'FAQ' },
    { value: 'glossary', label: 'Glossary' },
    { value: 'documentation', label: 'Documentation' },
];

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'archived', label: 'Archived' },
];

const visibilityOptions = [
    { value: 'public', label: 'Public - Visible to everyone' },
    { value: 'team', label: 'Team - Visible to team members only' },
    { value: 'private', label: 'Private - Visible to author only' },
];

export default function EditKnowledgeArticlePage() {
    const { uuid } = useParams<{ uuid: string }>();
    const navigate = useNavigate();

    const { data: article, isLoading: articleLoading } = useKnowledgeArticle(uuid);
    const { data: categories } = useKnowledgeCategories({ is_active: true });
    const { data: tags } = useKnowledgeTags();

    const updateArticle = useUpdateKnowledgeArticle();

    const {
        register,
        handleSubmit,
        setValue,
        formState: { errors, isSubmitting },
    } = useForm<UpdateArticleForm>({
        resolver: zodResolver(updateArticleSchema),
        defaultValues: {
            article_type: 'article',
            status: 'draft',
            visibility: 'team',
            is_featured: false,
        },
    });

    // Populate form with article data
    useEffect(() => {
        if (article) {
            setValue('title', article.title);
            setValue('slug', article.slug || '');
            setValue('excerpt', article.excerpt || '');
            setValue('content', article.content || '');
            setValue('category_id', article.category_id || null);
            setValue('article_type', article.article_type);
            setValue('status', article.status);
            setValue('visibility', article.visibility);
            setValue('featured_image', article.featured_image || '');
            setValue('meta_title', article.meta_title || '');
            setValue('meta_description', article.meta_description || '');
            setValue('meta_keywords', article.meta_keywords || '');
            setValue('is_featured', article.is_featured);
            setValue('published_at', article.published_at ? article.published_at.split('T')[0] : '');
            setValue('tag_ids', article.tags?.map(t => t.id) || []);
        }
    }, [article, setValue]);

    const onSubmit = (data: UpdateArticleForm) => {
        if (!uuid) return;
        updateArticle.mutate({ uuid, data }, {
            onSuccess: () => {
                toast.success('Article updated successfully');
                navigate(`/knowledge/articles/${uuid}`);
            },
            onError: (error) => {
                toast.error(error.message || 'Failed to update article');
            },
        });
    };

    if (articleLoading) {
        return (
            <div className="container mx-auto p-6 max-w-4xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-2xl">Loading...</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="space-y-2">
                                <Skeleton className="h-6 w-40" />
                                <Skeleton className="h-40 w-full" />
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="container mx-auto p-6 max-w-4xl">
            <div className="mb-6">
                <Link to="/knowledge" className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="mr-1 h-4 w-4" />
                    Back to Knowledge Base
                </Link>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Article</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="title">Title *</Label>
                            <Input
                                id="title"
                                {...register('title')}
                                placeholder="Enter article title"
                                className={errors.title ? 'border-destructive' : ''}
                            />
                            {errors.title && <p className="text-sm text-destructive">{errors.title.message}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                {...register('slug')}
                                placeholder="Auto-generated from title"
                            />
                            <p className="text-xs text-muted-foreground">Leave empty to auto-generate from title</p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="excerpt">Excerpt</Label>
                            <Textarea
                                id="excerpt"
                                {...register('excerpt')}
                                placeholder="Short summary for listings and SEO"
                                rows={3}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="content">Content</Label>
                            <Textarea
                                id="content"
                                {...register('content')}
                                placeholder="Write your article content here (supports Markdown/HTML)"
                                rows={20}
                                className="font-mono text-sm"
                            />
                            <p className="text-xs text-muted-foreground">Supports Markdown formatting</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Settings</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="category_id">Category</Label>
                                <Select
                                    id="category_id"
                                    {...register('category_id', { valueAsNumber: true })}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories?.map((cat) => (
                                            <SelectItem key={cat.id} value={cat.id}>{cat.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="article_type">Type *</Label>
                                <Select
                                    id="article_type"
                                    {...register('article_type')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {typeOptions.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Status *</Label>
                                <Select
                                    id="status"
                                    {...register('status')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statusOptions.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="visibility">Visibility *</Label>
                                <Select
                                    id="visibility"
                                    {...register('visibility')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select visibility" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {visibilityOptions.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tag_ids">Tags</Label>
                            <Select
                                id="tag_ids"
                                multiple
                                {...register('tag_ids', { valueAsNumber: true })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select tags" />
                                </SelectTrigger>
                                <SelectContent>
                                    {tags?.map((tag) => (
                                        <SelectItem key={tag.id} value={tag.id}>{tag.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">Hold Ctrl/Cmd to select multiple</p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="featured_image">Featured Image URL</Label>
                            <Input
                                id="featured_image"
                                {...register('featured_image')}
                                placeholder="https://example.com/image.jpg"
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <div className="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="is_featured"
                                    {...register('is_featured')}
                                />
                                <Label htmlFor="is_featured">Featured Article</Label>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="published_at">Published At</Label>
                            <Input
                                id="published_at"
                                type="date"
                                {...register('published_at')}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>SEO Settings</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="meta_title">Meta Title</Label>
                            <Input
                                id="meta_title"
                                {...register('meta_title')}
                                placeholder="Defaults to article title"
                                maxLength={255}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="meta_description">Meta Description</Label>
                            <Textarea
                                id="meta_description"
                                {...register('meta_description')}
                                placeholder="Brief description for search engines"
                                rows={3}
                                maxLength={500}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="meta_keywords">Meta Keywords (comma separated)</Label>
                            <Input
                                id="meta_keywords"
                                {...register('meta_keywords')}
                                placeholder="keyword1, keyword2, keyword3"
                                maxLength={500}
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-3 sticky bottom-4 bg-white/95 backdrop-blur pb-4">
                    <Link to="/knowledge" className="btn-ghost">
                        Cancel
                    </Link>
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting ? (
                            <>
                                <span className="animate-spin mr-2">⏳</span>
                                Saving...
                            </>
                        ) : (
                            <>
                                <Save className="mr-2 h-4 w-4" />
                                Save Changes
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}