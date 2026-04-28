import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CircleDashed } from 'lucide-react';

type ModuleMetric = {
    label: string;
    value: string;
    trend: string;
};

type ModuleOverview = {
    module: string;
    title: string;
    description: string;
    status: string;
    metrics: ModuleMetric[];
    nextSteps: string[];
};

export default function ModuleOverviewPage({ overview }: { overview: ModuleOverview }) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: overview.title,
            href: `/${overview.module === 'auth' ? 'security' : overview.module}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={overview.title} />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 tracking-[-0.02em] dark:bg-zinc-950">
                <section className="grid gap-4">
                    <div className="flex flex-col items-start justify-between gap-4 md:flex-row">
                        <div className="max-w-3xl">
                            <p className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                <span className="size-2 rounded-full bg-emerald-500" />
                                Modulo operativo
                            </p>
                            <h1 className="mt-3 text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">{overview.title}</h1>
                            <p className="mt-2 text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">{overview.description}</p>
                        </div>
                        <span className="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 py-3 text-sm font-normal text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                            <span className="size-2 rounded-full bg-emerald-500" />
                            {overview.status}
                        </span>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    {overview.metrics.map((metric) => (
                        <Card key={metric.label} className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="grid gap-3 p-6">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{metric.label}</p>
                                <p className="text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">{metric.value}</p>
                                <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    <span className="size-2 rounded-full bg-sky-500" />
                                    {metric.trend}
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-3">
                    <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Siguientes decisiones de dominio</h2>
                    <Card className="overflow-hidden rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        {overview.nextSteps.map((step) => (
                            <div
                                key={step}
                                className="flex items-center gap-3 border-b border-neutral-200 px-4 py-3 last:border-b-0 hover:bg-neutral-100 dark:border-zinc-800 dark:hover:bg-zinc-800/50"
                            >
                                <CircleDashed className="size-4 shrink-0 text-neutral-500 dark:text-zinc-400" />
                                <span className="text-sm font-normal text-neutral-900 dark:text-zinc-50">{step}</span>
                            </div>
                        ))}
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
