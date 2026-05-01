export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <span className="text-xs font-bold tracking-tight">Fr</span>
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-sm font-semibold leading-none tracking-tight">Frontier</span>
                <span className="truncate text-[10px] leading-none text-sidebar-foreground/50 mt-0.5">Hotel ERP</span>
            </div>
        </>
    );
}
