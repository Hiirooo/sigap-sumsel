export default function ApplicationLogo({ logoSrc, ...props }) {
    return <img {...props} src={logoSrc ?? "/logo-bhp.jpg"} alt="Logo Biro Humas dan Protokol" />;
}
