import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link } from '@inertiajs/react';
import { Clock3 } from 'lucide-react';

export default function PendingAssignment() {
    return (
        <AuthLayout
            title="Asignacion pendiente"
            description="Tu cuenta ya existe, pero gerencia debe asignarte una o varias areas de trabajo antes de entrar a Frontier."
        >
            <Head title="Asignacion pendiente" />

            <div className="grid gap-6 tracking-[-0.02em]">
                <div className="rounded-xl border border-neutral-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="flex items-start gap-4">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border border-neutral-200 dark:border-zinc-800">
                            <Clock3 className="size-5 text-neutral-500 dark:text-zinc-400" />
                        </div>
                        <div className="grid gap-2">
                            <h2 className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                Esperando validacion de gerencia
                            </h2>
                            <p className="text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                                Cuando gerencia te asigne un area como recepcion, limpieza, lavanderia, cocina o mantenimiento, Frontier activara tu
                                espacio operativo.
                            </p>
                            <span className="mt-2 flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                <span className="size-2 rounded-full bg-amber-500" />
                                Pendiente de area operativa
                            </span>
                        </div>
                    </div>
                </div>

                <Button asChild variant="outline" className="rounded-lg tracking-[-0.02em]">
                    <Link href={route('logout')} method="post">
                        Cerrar sesion
                    </Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
