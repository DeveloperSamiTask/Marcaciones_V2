import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            {/* <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div> */}
            <div className="grid flex-1 text-left text-xl">
                <span className="truncate leading-none font-bold">Sistema de Marcación</span>
            </div>
        </>
    );
}
