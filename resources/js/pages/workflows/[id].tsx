import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { workflowApi } from '@/lib/api/workflow';
import { useWorkflowStore } from '@/stores/workflow-store';
import type { Workflow, WorkflowRun } from '@/types/workflow';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Play, Download, RefreshCw, XCircle } from 'lucide-react';

const runStatusColors: Record<string, string> = {
  pending: 'bg-gray-100 text-gray-800',
  running: 'bg-blue-100 text-blue-800',
  waiting: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  failed: 'bg-red-100 text-red-800',
  cancelled: 'bg-gray-100 text-gray-800',
  retrying: 'bg-purple-100 text-purple-800',
};

export default function WorkflowDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { selectedWorkflow, setSelectedWorkflow, workflowRuns, setWorkflowRuns } = useWorkflowStore();
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (id) loadWorkflow(parseInt(id));
  }, [id]);

  async function loadWorkflow(workflowId: number) {
    setLoading(true);
    try {
      const workflow = await workflowApi.get(workflowId);
      setSelectedWorkflow(workflow);
      const runsRes = await workflowApi.runs(workflowId);
      setWorkflowRuns(runsRes.data);
    } finally {
      setLoading(false);
    }
  }

  async function handleExecute() {
    if (!selectedWorkflow) return;
    await workflowApi.execute(selectedWorkflow.id, {}, 'manual');
    loadWorkflow(selectedWorkflow.id);
  }

  async function handleRetry(run: WorkflowRun) {
    await workflowApi.retryRun(run.id);
    loadWorkflow(run.workflow_id);
  }

  async function handleCancel(run: WorkflowRun) {
    await workflowApi.cancelRun(run.id);
    loadWorkflow(run.workflow_id);
  }

  async function handleExport() {
    if (!selectedWorkflow) return;
    const data = await workflowApi.export(selectedWorkflow.id);
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${selectedWorkflow.name.replace(/\s+/g, '_').toLowerCase()}.json`;
    a.click();
    URL.revokeObjectURL(url);
  }

  if (loading || !selectedWorkflow) return <div>Loading...</div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" onClick={() => navigate('/workflows')}>
            <ArrowLeft className="w-4 h-4 mr-2" />Back
          </Button>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-2xl font-bold">{selectedWorkflow.name}</h1>
              <Badge className={selectedWorkflow.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}>
                {selectedWorkflow.status}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              {selectedWorkflow.category} &middot; v{selectedWorkflow.version} &middot; {selectedWorkflow.execution_mode}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={handleExport}><Download className="w-4 h-4 mr-2" />Export</Button>
          <Button onClick={handleExecute}><Play className="w-4 h-4 mr-2" />Execute Now</Button>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="runs">Execution History</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4 mt-4">
          {selectedWorkflow.description && (
            <Card>
              <CardHeader><CardTitle>Description</CardTitle></CardHeader>
              <CardContent><p className="text-muted-foreground">{selectedWorkflow.description}</p></CardContent>
            </Card>
          )}

          <div className="grid grid-cols-3 gap-4">
            <Card>
              <CardHeader><CardTitle className="text-sm">Total Runs</CardTitle></CardHeader>
              <CardContent><p className="text-2xl font-bold">{workflowRuns.length}</p></CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle className="text-sm">Successful</CardTitle></CardHeader>
              <CardContent><p className="text-2xl font-bold text-green-600">{workflowRuns.filter((r) => r.status === 'completed').length}</p></CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle className="text-sm">Failed</CardTitle></CardHeader>
              <CardContent><p className="text-2xl font-bold text-red-600">{workflowRuns.filter((r) => r.status === 'failed').length}</p></CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="runs" className="space-y-4 mt-4">
          {workflowRuns.length === 0 ? (
            <Card>
              <CardContent className="py-8 text-center text-muted-foreground">
                No executions yet. Click "Execute Now" to run this workflow.
              </CardContent>
            </Card>
          ) : (
            workflowRuns.map((run) => (
              <Card key={run.id}>
                <CardHeader className="py-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Badge className={runStatusColors[run.status]}>{run.status}</Badge>
                      <span className="text-sm text-muted-foreground">
                        {run.trigger_type} &middot; Attempt {run.attempts}/{run.max_attempts}
                      </span>
                    </div>
                    <div className="flex gap-1">
                      {run.status === 'failed' && (
                        <Button size="sm" variant="ghost" onClick={() => handleRetry(run)}>
                          <RefreshCw className="w-4 h-4" />
                        </Button>
                      )}
                      {(run.status === 'running' || run.status === 'waiting') && (
                        <Button size="sm" variant="ghost" onClick={() => handleCancel(run)}>
                          <XCircle className="w-4 h-4" />
                        </Button>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="py-2">
                  <div className="text-xs text-muted-foreground">
                    {run.started_at && <span>Started: {new Date(run.started_at).toLocaleString()} </span>}
                    {run.finished_at && <span>&middot; Finished: {new Date(run.finished_at).toLocaleString()}</span>}
                    {run.result?.branch_key && <span>&middot; Branch: {run.result.branch_key}</span>}
                  </div>
                </CardContent>
              </Card>
            ))
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
