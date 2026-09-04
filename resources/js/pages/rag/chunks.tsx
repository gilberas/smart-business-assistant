'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useRAGChunks, useRAGChunkStats, useRAGChunkingPreview } from '@/hooks/useRAG';

export default function RAGChunksPage() {
    const [filters, setFilters] = useState<Record<string, string>>({});
    const [chunkContent, setChunkContent] = useState('');
    const [chunkStrategy, setChunkStrategy] = useState('paragraph');

    const { data: chunksData } = useRAGChunks(filters);
    const { data: stats } = useRAGChunkStats();
    const chunkingPreview = useRAGChunkingPreview();

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold">Chunk Viewer</h1>
                <p className="text-muted-foreground">Inspect knowledge chunks and test chunking strategies</p>
            </div>

            {stats && (
                <div className="grid grid-cols-4 gap-4">
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Total Chunks</CardTitle></CardHeader>
                        <CardContent>
                            <span className="text-2xl font-bold">{stats.total_chunks as number}</span>
                        </CardContent>
                    </Card>
                    {Object.entries(stats.by_source as Record<string, number> || {}).map(([source, count]) => (
                        <Card key={source}>
                            <CardHeader><CardTitle className="text-sm">{source}</CardTitle></CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold">{count}</span>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <Tabs defaultValue="browse">
                <TabsList>
                    <TabsTrigger value="browse">Browse Chunks</TabsTrigger>
                    <TabsTrigger value="preview">Test Chunking</TabsTrigger>
                </TabsList>

                <TabsContent value="browse">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex gap-4 mb-4">
                                <div className="flex-1">
                                    <Input
                                        placeholder="Search chunks..."
                                        value={filters.search || ''}
                                        onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                                    />
                                </div>
                                <Select
                                    value={filters.source_type || ''}
                                    onValueChange={(v) => setFilters({ ...filters, source_type: v })}
                                >
                                    <SelectTrigger className="w-40">
                                        <SelectValue placeholder="All Sources" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Sources</SelectItem>
                                        <SelectItem value="knowledge_article">Articles</SelectItem>
                                        <SelectItem value="faq">FAQs</SelectItem>
                                        <SelectItem value="product">Products</SelectItem>
                                        <SelectItem value="service">Services</SelectItem>
                                        <SelectItem value="business_information">Business Info</SelectItem>
                                        <SelectItem value="policy">Policies</SelectItem>
                                        <SelectItem value="document">Documents</SelectItem>
                                        <SelectItem value="conversation">Conversations</SelectItem>
                                        <SelectItem value="customer">Customers</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Index</TableHead>
                                        <TableHead>Source</TableHead>
                                        <TableHead>Strategy</TableHead>
                                        <TableHead>Heading</TableHead>
                                        <TableHead>Words</TableHead>
                                        <TableHead>Tokens</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {chunksData?.data?.map((chunk: Record<string, unknown>) => (
                                        <TableRow key={chunk.id as number}>
                                            <TableCell>{chunk.chunk_index as number}</TableCell>
                                            <TableCell><Badge variant="outline">{chunk.source_type as string}</Badge></TableCell>
                                            <TableCell><Badge variant="secondary">{chunk.strategy as string}</Badge></TableCell>
                                            <TableCell className="max-w-[200px] truncate">
                                                {(chunk.heading as string) || '-'}
                                            </TableCell>
                                            <TableCell>{chunk.word_count as number}</TableCell>
                                            <TableCell>{chunk.token_estimate as number}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="preview">
                    <Card>
                        <CardContent className="pt-6 space-y-4">
                            <div>
                                <Label>Content to Chunk</Label>
                                <textarea
                                    className="w-full h-40 p-2 border rounded-md bg-background font-mono text-sm"
                                    value={chunkContent}
                                    onChange={(e) => setChunkContent(e.target.value)}
                                    placeholder="Paste or type content to test chunking..."
                                />
                            </div>
                            <div className="flex gap-4 items-end">
                                <div className="w-48">
                                    <Label>Strategy</Label>
                                    <Select value={chunkStrategy} onValueChange={setChunkStrategy}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="paragraph">Paragraph</SelectItem>
                                            <SelectItem value="section">Section</SelectItem>
                                            <SelectItem value="sliding_window">Sliding Window</SelectItem>
                                            <SelectItem value="fixed_size">Fixed Size</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button
                                    onClick={() => chunkingPreview.mutate({ content: chunkContent, strategy: chunkStrategy })}
                                    disabled={!chunkContent.trim() || chunkingPreview.isPending}
                                >
                                    {chunkingPreview.isPending ? 'Chunking...' : 'Preview Chunks'}
                                </Button>
                            </div>

                            {chunkingPreview.data && (
                                <div className="space-y-2">
                                    <Label>Result: {chunkingPreview.data.total_chunks} chunks</Label>
                                    {chunkingPreview.data.chunks.map((chunk: Record<string, unknown>) => (
                                        <Card key={chunk.chunk_index as number}>
                                            <CardContent className="pt-4">
                                                <div className="flex justify-between text-sm text-muted-foreground mb-2">
                                                    <span>Chunk #{chunk.chunk_index as number}</span>
                                                    <span>{(chunk.word_count as number)} words | {(chunk.token_estimate as number)} tokens</span>
                                                </div>
                                                <pre className="text-xs bg-muted p-3 rounded overflow-auto max-h-32">
                                                    {chunk.content as string}
                                                </pre>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}
