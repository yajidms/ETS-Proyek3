import "./globals.css";

export const metadata = {
  title: "Portal Gaji DPR",
  description: "Transparansi gaji anggota DPR",
};

export default function RootLayout({ children }) {
  return (
    <html lang="id">
      <body>{children}</body>
    </html>
  );
}
