import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { workflowApi } from '@/lib/api/workflow';
import { useWorkflowStore } from '@/stores/workflow-store';
import type { Workflow, WorkflowFilters, WorkflowStatus } from '@/types/workflow';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Play, Download, FileUp } from 'lucide-react';

const statusColors: Record<WorkflowStatus, string> = {
  draft: 'bg-gray-100 text-gray-800',
  active: 'bg-green-100 text-green-800',
  paused: 'bg-yellow-100 text-yellow-800',
  archived: 'bg-red-100 text-red-800',
};

export default function WorkflowDashboard() {
  const navigate = useNavigate();
  const { workflows, totalWorkflows, isLoading, setWorkflows, setTotalWorkflows, setLoading } = useWorkflowStore();
  const [filters, setFilters] = useState<WorkflowFilters>({ page: 1, per_page: 15 });
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadWorkflows();
  }, [filters]);

  async function loadWorkflows() {
    setLoading(true);
    try {
      const response = await workflowApi.list(filters);
      setWorkflows(response.data);
      setTotalWorkflows(response.meta.total);
    } finally {
      setLoading(false);
    }
  }

  function handleSearch(e: React.FormEvent) {
    e.preventDefault();
    setFilters((prev) => ({ ...prev, search, page: 1 }));
  }

  async function handleToggleStatus(workflow: Workflow) {
    if (workflow.status === 'active') {
      await workflowApi.disable(workflow.id);
    } else {
      await workflowApi.enable(workflow.id);
    }
    loadWorkflows();
  }

  async function handleExecute(workflow: Workflow) {
    await workflowApi.execute(workflow.id, {}, 'manual');
    loadWorkflows();
  }

  async function handleExport(workflow: Workflow) {
    const data = await workflowApi.export(workflow.id);
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${workflow.name.replace(/\s+/g, '_').toLowerCase()}.json`;
    a.click();
    URL.revokeObjectURL(url);
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Workflows</h1>
          <p className="text-muted-foreground">Automate repetitive tasks with visual workflows</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => navigate('/workflows/import')}>
            <FileUp className="w-4 h-4 mr-2" />
            Import
          </Button>
          <Button onClick={() => navigate('/workflows/create')}>
            <Plus className="w-4 h-4 mr-2" />
            New Workflow
          </Button>
        </div>
      </div>

      <form onSubmit={handleSearch} className="flex gap-2">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input
            placeholder="Search workflows..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-10"
          />
        </div>
      </form>

      <div className="grid gap-4">
        {workflows.map((workflow) => (
          <Card key={workflow.id} className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => navigate(`/workflows/${workflow.id}`)}>
            <CardHeader className="pb-2">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <CardTitle className="text-lg">{workflow.name}</CardTitle>
                  <Badge className={statusColors[workflow.status]}>{workflow.status}</Badge>
                </div>
                <div className="flex gap-1" onClick={(e) => e.stopPropagation()}>
                  <Button size="sm" variant="ghost" onClick={() => handleExecute(workflow)} title="Execute now">
                    <Play className="w-4 h-4" />
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => handleExport(workflow)} title="Export">
                    <Download className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-4 text-sm text-muted-foreground">
                <span>{workflow.category}</span>
                <span>v{workflow.version}</span>
                <span>{workflow.execution_mode}</span>
                {workflow.description && <span className="truncate max-w-md">{workflow.description}</span>}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {totalWorkflows > 15 && (
        <div className="flex justify-center gap-2">
          <Button variant="outline" disabled={filters.page === 1} onClick={() => setFilters((prev) => ({ ...prev, page: (prev.page || 1) - 1 }))}>
            Previous
          </Button>
          <span className="flex items-center text-sm text-muted-foreground">Page {filters.page}</span>
          <Button variant="outline" disabled={workflows.length < 15} onClick={() => setFilters((prev) => ({ ...prev, page: (prev.page || 1) + 1 }))}>
            Next
          </Button>
        </div>
      )}
    </div>
  );
}
