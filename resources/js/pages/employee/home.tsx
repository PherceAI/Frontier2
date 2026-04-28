import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { LogOut, Smartphone } from 'lucide-react';

type Area = {
    id: number;
    name: string;
    slug: string;
};

export default function EmployeeHome({ employee }: { employee: { name: string; areas: Area[] } }) {
    return (
        <>
            <Head title="Operativo" />
            <main className="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-zinc-950 dark:text-zinc-50">
                <div className="mx-auto flex min-h-screen w-full max-w-md flex-col gap-6 px-4 py-4 tracking-[-0.02em]">
                    <header className="flex items-center justify-between gap-4">
                        <div className="grid gap-1">
                            <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Frontier operativo</p>
                            <h1 className="text-2xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Mi area</h1>
                        </div>
                        <Button asChild variant="ghost" size="icon" className="rounded-lg">
                            <Link href={route('logout')} method="post" aria-label="Cerrar sesion">
                                <LogOut className="size-5 text-neutral-500 dark:text-zinc-400" />
                            </Link>
                        </Button>
                    </header>

                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardContent className="grid gap-4 p-6">
                            <div className="flex items-start gap-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 dark:border-zinc-800">
                                    <Smartphone className="size-5 text-neutral-500 dark:text-zinc-400" />
                                </div>
                                <div className="grid gap-2">
                                    <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                        Hola, {employee.name}
                                    </h2>
                                    <p className="text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                                        Tu portal operativo mobile esta activo. La experiencia especifica de tus areas se ira habilitando por modulo.
                                    </p>
                                </div>
                            </div>
                            <div className="grid gap-2">
                                {employee.areas.map((area) => (
                                    <span key={area.id} className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                        <span className="size-2 rounded-full bg-emerald-500" />
                                        {area.name}
                                    </span>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
