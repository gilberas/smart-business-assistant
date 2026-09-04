'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Loader2 } from 'lucide-react';
import { useRAGSearch, useQueryAnalysis } from '@/hooks/useRAG';
import { useDebounce } from '@/hooks/useDebounce';

export default function RAGSearchPage() {
    const [query, setQuery] = useState('');
    const [strategy, setStrategy] = useState('hybrid');
    const [sources, setSources] = useState<string[]>([]);
    const debouncedQuery = useDebounce(query, 300);

    const { data: analysis } = useQueryAnalysis(debouncedQuery.length >= 2 ? debouncedQuery : null);
    const { data: searchResults, isLoading } = useRAGSearch(
        debouncedQuery.length >= 2
            ? { query: debouncedQuery, strategy, sources: sources.length ? sources : undefined }
            : null,
    );

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold">RAG Search Debugger</h1>
                <p className="max-w-2xl text-muted-foreground">
                    Test and debug knowledge retrieval in real-time. Enter a customer question to see
                    which knowledge base articles and FAQs the AI assistant would surface as context
                    when answering.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Search Query</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex gap-4">
                        <div className="flex-1">
                            <Label htmlFor="query">Query</Label>
                            <Input
                                id="query"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Type a customer question..."
                            />
                        </div>
                        <div className="w-48">
                            <Label>Strategy</Label>
                            <Select value={strategy} onValueChange={setStrategy}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="hybrid">Hybrid</SelectItem>
                                    <SelectItem value="keyword">Keyword</SelectItem>
                                    <SelectItem value="fulltext">Fulltext</SelectItem>
                                    <SelectItem value="tag">Tag</SelectItem>
                                    <SelectItem value="category">Category</SelectItem>
                                    <SelectItem value="metadata">Metadata</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {analysis && (
                <Card>
                    <CardHeader>
                        <CardTitle>Query Analysis</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <Label>Intent</Label>
                                <Badge variant="secondary" className="mt-1">{analysis.intent_label}</Badge>
                            </div>
                            <div>
                                <Label>Relevant Sources</Label>
                                <div className="flex flex-wrap gap-1 mt-1">
                                    {analysis.relevant_sources.map((s: string) => (
                                        <Badge key={s} variant="outline">{s}</Badge>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <Label>Keywords</Label>
                                <div className="flex flex-wrap gap-1 mt-1">
                                    {(analysis.parsed_query?.keywords as string[] || []).map((k: string) => (
                                        <Badge key={k}>{k}</Badge>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>
                        Results
                        {searchResults && (
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                {searchResults.total_results} found in {searchResults.execution_time_ms.toFixed(1)}ms
                            </span>
                        )}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="h-6 w-6 animate-spin" />
                        </div>
                    ) : searchResults?.results?.length ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Score</TableHead>
                                    <TableHead>Source</TableHead>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Content Preview</TableHead>
                                    <TableHead>Category</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {searchResults.results.map((r, i) => (
                                    <TableRow key={`${r.source_type}-${r.source_id}-${i}`}>
                                        <TableCell>
                                            <Badge variant={r.relevance_score > 0.5 ? 'default' : 'secondary'}>
                                                {(r.relevance_score * 100).toFixed(0)}%
                                            </Badge>
                                        </TableCell>
                                        <TableCell><Badge variant="outline">{r.source_type}</Badge></TableCell>
                                        <TableCell className="font-medium max-w-[200px] truncate">{r.title}</TableCell>
                                        <TableCell className="max-w-[300px] text-sm text-muted-foreground truncate">
                                            {r.content?.substring(0, 150)}
                                        </TableCell>
                                        <TableCell>{r.category || '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : query ? (
                        <p className="text-center text-muted-foreground py-8">No results found</p>
                    ) : (
                        <p className="text-center text-muted-foreground py-8">
                            <Search className="inline mr-2 h-4 w-4" />
                            Type a query to search
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
