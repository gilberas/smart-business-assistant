'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRAGContext } from '@/hooks/useRAG';

export default function RAGContextPage() {
    const [query, setQuery] = useState('');
    const [submittedQuery, setSubmittedQuery] = useState('');

    const { data: context, isLoading } = useRAGContext(
        submittedQuery ? { query: submittedQuery } : null,
    );

    const handleSearch = () => {
        if (query.trim()) setSubmittedQuery(query.trim());
    };

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold">Context Preview</h1>
                <p className="text-muted-foreground">Preview the structured context that will be sent to the AI</p>
            </div>

            <Card>
                <CardContent className="pt-6">
                    <div className="flex gap-4">
                        <div className="flex-1">
                            <Input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Enter a query to build context..."
                                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                            />
                        </div>
                        <Button onClick={handleSearch} disabled={isLoading || !query.trim()}>
                            {isLoading ? 'Building...' : 'Build Context'}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {context && (
                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="business">Business Profile</TabsTrigger>
                        <TabsTrigger value="faqs">FAQs ({context.relevant_faqs.length})</TabsTrigger>
                        <TabsTrigger value="articles">Articles ({context.relevant_articles.length})</TabsTrigger>
                        <TabsTrigger value="products">Products ({context.relevant_products.length})</TabsTrigger>
                        <TabsTrigger value="services">Services ({context.relevant_services.length})</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <div className="grid grid-cols-4 gap-4">
                            <Card>
                                <CardHeader><CardTitle className="text-lg">Confidence</CardTitle></CardHeader>
                                <CardContent>
                                    <span className="text-2xl font-bold">{(context.confidence_score * 100).toFixed(0)}%</span>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-lg">Token Estimate</CardTitle></CardHeader>
                                <CardContent>
                                    <span className="text-2xl font-bold">{context.token_estimate.toLocaleString()}</span>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-lg">Execution Time</CardTitle></CardHeader>
                                <CardContent>
                                    <span className="text-2xl font-bold">{context.execution_time_ms.toFixed(1)}ms</span>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-lg">Citations</CardTitle></CardHeader>
                                <CardContent>
                                    <span className="text-2xl font-bold">{context.suggested_citations.length}</span>
                                </CardContent>
                            </Card>
                        </div>

                        {context.suggested_citations.length > 0 && (
                            <Card className="mt-4">
                                <CardHeader><CardTitle>Suggested Citations</CardTitle></CardHeader>
                                <CardContent>
                                    {context.suggested_citations.map((c) => (
                                        <div key={c.index} className="flex items-center gap-2 mb-2">
                                            <Badge>{c.index}</Badge>
                                            <span>{c.title}</span>
                                            <Badge variant="outline">{c.source_type}</Badge>
                                            <span className="text-sm text-muted-foreground">
                                                {(c.relevance * 100).toFixed(0)}% match
                                            </span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="business">
                        <Card>
                            <CardContent className="pt-6">
                                <pre className="text-sm bg-muted p-4 rounded-lg overflow-auto max-h-96">
                                    {JSON.stringify(context.business_profile, null, 2)}
                                </pre>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="faqs">
                        {context.relevant_faqs.map((faq, i) => (
                            <Card key={i} className="mb-4">
                                <CardHeader><CardTitle className="text-lg">{faq.title}</CardTitle></CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">{faq.content}</p>
                                    <div className="mt-2">
                                        <Badge>{(faq.relevance_score * 100).toFixed(0)}%</Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>

                    <TabsContent value="articles">
                        {context.relevant_articles.map((article, i) => (
                            <Card key={i} className="mb-4">
                                <CardHeader><CardTitle className="text-lg">{article.title}</CardTitle></CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">
                                        {article.content?.substring(0, 500)}
                                    </p>
                                    <div className="mt-2 flex gap-2">
                                        <Badge>{(article.relevance_score * 100).toFixed(0)}%</Badge>
                                        {article.category && <Badge variant="outline">{article.category}</Badge>}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>

                    <TabsContent value="products">
                        {context.relevant_products.map((product, i) => (
                            <Card key={i} className="mb-4">
                                <CardHeader><CardTitle className="text-lg">{product.title}</CardTitle></CardHeader>
                                <CardContent>
                                    {(() => {
                                        const metadata = product.metadata as Record<string, unknown> | null;
                                        const currency = typeof metadata?.currency === 'string' ? metadata.currency : '';
                                        const price = typeof metadata?.price === 'string' || typeof metadata?.price === 'number'
                                            ? metadata.price
                                            : '';

                                        return (
                                            <>
                                                <p className="text-sm text-muted-foreground">{product.content}</p>
                                                <div className="mt-2 flex gap-2">
                                                    <Badge>{(product.relevance_score * 100).toFixed(0)}%</Badge>
                                                    {price && (
                                                        <Badge variant="secondary">
                                                            {currency} {String(price)}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </>
                                        );
                                    })()}
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>

                    <TabsContent value="services">
                        {context.relevant_services.map((service, i) => (
                            <Card key={i} className="mb-4">
                                <CardHeader><CardTitle className="text-lg">{service.title}</CardTitle></CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">{service.content}</p>
                                    <div className="mt-2 flex gap-2">
                                        <Badge>{(service.relevance_score * 100).toFixed(0)}%</Badge>
                                        {service.category && <Badge variant="outline">{service.category}</Badge>}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>
                </Tabs>
            )}
        </div>
    );
}
