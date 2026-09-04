'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRAGSystemInfo, useRAGLogs, useRAGRankingPreview, useRAGPromptPreview } from '@/hooks/useRAG';
import { Loader2 } from 'lucide-react';

export default function RAGDiagnosticsPage() {
    const { data: systemInfo } = useRAGSystemInfo();
    const { data: logsData } = useRAGLogs();
    const rankingPreview = useRAGRankingPreview();
    const promptPreview = useRAGPromptPreview();

    const [rankingQuery, setRankingQuery] = useState('');
    const [rankingSource, setRankingSource] = useState('');
    const [rankingSourceId, setRankingSourceId] = useState('');
    const [rankingResult, setRankingResult] = useState<Record<string, unknown> | null>(null);

    const [promptQuery, setPromptQuery] = useState('');
    const [promptMessage, setPromptMessage] = useState('');
    const [promptResult, setPromptResult] = useState<Record<string, unknown> | null>(null);

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold">Retrieval Diagnostics</h1>
                <p className="text-muted-foreground">System configuration, retrieval logs, and diagnostic tools</p>
            </div>

            <Tabs defaultValue="system">
                <TabsList>
                    <TabsTrigger value="system">System Info</TabsTrigger>
                    <TabsTrigger value="logs">Retrieval Logs</TabsTrigger>
                    <TabsTrigger value="ranking">Ranking Test</TabsTrigger>
                    <TabsTrigger value="prompt">Prompt Preview</TabsTrigger>
                </TabsList>

                <TabsContent value="system">
                    {systemInfo ? (
                        <div className="grid grid-cols-2 gap-4">
                            <Card>
                                <CardHeader><CardTitle>Chunking Configuration</CardTitle></CardHeader>
                                <CardContent>
                                    <pre className="text-sm bg-muted p-4 rounded-lg overflow-auto max-h-48">
                                        {JSON.stringify(systemInfo.chunking, null, 2)}
                                    </pre>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle>Retrieval Configuration</CardTitle></CardHeader>
                                <CardContent>
                                    <pre className="text-sm bg-muted p-4 rounded-lg overflow-auto max-h-48">
                                        {JSON.stringify(systemInfo.retrieval, null, 2)}
                                    </pre>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle>Ranking Weights</CardTitle></CardHeader>
                                <CardContent>
                                    <pre className="text-sm bg-muted p-4 rounded-lg overflow-auto max-h-48">
                                        {JSON.stringify(systemInfo.ranking, null, 2)}
                                    </pre>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle>Available Sources</CardTitle></CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {(systemInfo.sources as string[] || []).map((s: string) => (
                                            <Badge key={s} variant="outline">{s}</Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        <div className="flex justify-center py-8"><Loader2 className="h-6 w-6 animate-spin" /></div>
                    )}
                </TabsContent>

                <TabsContent value="logs">
                    <Card>
                        <CardContent className="pt-6">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Time</TableHead>
                                        <TableHead>Query</TableHead>
                                        <TableHead>Intent</TableHead>
                                        <TableHead>Strategy</TableHead>
                                        <TableHead>Results</TableHead>
                                        <TableHead>Time (ms)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logsData?.data?.length ? (
                                        logsData.data.map((log: Record<string, unknown>) => (
                                            <TableRow key={log.id as number}>
                                                <TableCell className="text-xs">
                                                    {new Date(log.created_at as string).toLocaleTimeString()}
                                                </TableCell>
                                                <TableCell className="max-w-[200px] truncate">
                                                    {log.query as string}
                                                </TableCell>
                                                <TableCell><Badge variant="secondary">{log.intent as string}</Badge></TableCell>
                                                <TableCell><Badge variant="outline">{log.primary_strategy as string}</Badge></TableCell>
                                                <TableCell>{log.total_results as number}</TableCell>
                                                <TableCell>{(log.execution_time_ms as number).toFixed(1)}</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center text-muted-foreground">
                                                No retrieval logs yet
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="ranking">
                    <Card>
                        <CardContent className="pt-6 space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <Label>Query</Label>
                                    <Input
                                        value={rankingQuery}
                                        onChange={(e) => setRankingQuery(e.target.value)}
                                        placeholder="Search query"
                                    />
                                </div>
                                <div>
                                    <Label>Source Type</Label>
                                    <Input
                                        value={rankingSource}
                                        onChange={(e) => setRankingSource(e.target.value)}
                                        placeholder="e.g., knowledge_article"
                                    />
                                </div>
                                <div>
                                    <Label>Source ID</Label>
                                    <Input
                                        value={rankingSourceId}
                                        onChange={(e) => setRankingSourceId(e.target.value)}
                                        placeholder="e.g., 1"
                                    />
                                </div>
                            </div>
                            <Button
                                onClick={() => {
                                    rankingPreview.mutate(
                                        {
                                            query: rankingQuery,
                                            source_type: rankingSource,
                                            source_id: parseInt(rankingSourceId),
                                        },
                                        {
                                            onSuccess: (data) => setRankingResult(data as unknown as Record<string, unknown>),
                                        },
                                    );
                                }}
                                disabled={!rankingQuery || !rankingSource || !rankingSourceId || rankingPreview.isPending}
                            >
                                {rankingPreview.isPending ? 'Scoring...' : 'Test Ranking'}
                            </Button>

                            {rankingResult && (
                                <Card>
                                    <CardHeader><CardTitle>Ranking Breakdown</CardTitle></CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {Object.entries(rankingResult.ranking_scores as Record<string, number> || {}).map(([factor, score]) => (
                                                <div key={factor} className="flex items-center gap-4">
                                                    <span className="w-40 text-sm">{factor}</span>
                                                    <div className="flex-1 bg-muted rounded-full h-3">
                                                        <div
                                                            className="bg-primary h-3 rounded-full"
                                                            style={{ width: `${(score as number) * 100}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-sm font-mono w-16 text-right">
                                                        {(score as number).toFixed(3)}
                                                    </span>
                                                </div>
                                            ))}
                                            <div className="pt-2 border-t flex items-center gap-4">
                                                <span className="w-40 font-bold">Total Score</span>
                                                <span className="font-mono text-lg font-bold">
                                                    {(rankingResult.total_score as number).toFixed(4)}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="prompt">
                    <Card>
                        <CardContent className="pt-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Query / Context</Label>
                                    <Input
                                        value={promptQuery}
                                        onChange={(e) => setPromptQuery(e.target.value)}
                                        placeholder="What is being asked?"
                                    />
                                </div>
                                <div>
                                    <Label>User Message</Label>
                                    <Input
                                        value={promptMessage}
                                        onChange={(e) => setPromptMessage(e.target.value)}
                                        placeholder="The actual user message"
                                    />
                                </div>
                            </div>
                            <Button
                                onClick={() => {
                                    promptPreview.mutate(
                                        { query: promptQuery, user_message: promptMessage },
                                        { onSuccess: (data) => setPromptResult(data as unknown as Record<string, unknown>) },
                                    );
                                }}
                                disabled={!promptQuery || !promptMessage || promptPreview.isPending}
                            >
                                {promptPreview.isPending ? 'Assembling...' : 'Assemble Prompt'}
                            </Button>

                            {promptResult && (
                                <div className="space-y-4">
                                    <div className="flex gap-4">
                                        <Badge variant="secondary">
                                            {promptResult.sections?.total_tokens} tokens
                                        </Badge>
                                        <Badge variant={promptResult.sections?.was_truncated ? 'destructive' : 'default'}>
                                            {promptResult.sections?.was_truncated ? 'Truncated' : 'Full'}
                                        </Badge>
                                    </div>
                                    <Card>
                                        <CardContent className="pt-4">
                                            <pre className="text-xs bg-muted p-4 rounded-lg overflow-auto max-h-96 whitespace-pre-wrap font-mono">
                                                {promptResult.assembled as string}
                                            </pre>
                                        </CardContent>
                                    </Card>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}
