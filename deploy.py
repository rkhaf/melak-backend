#!/usr/bin/env python3
"""
deploy.py - Push Kaliwedi Backend ke VPS via SSH
Usage: python deploy.py
"""

import subprocess
import sys
import shutil

# ==================== CONFIG ====================
VPS_IP        = "208.76.40.197"
VPS_PORT      = 15188
VPS_USER      = "ubuntu"
VPS_PASSWORD  = "mio@VPS"       # fallback jika sshpass tersedia
REMOTE_DIR    = "/var/www/ngingu"
CONTAINER     = "ngingu-be"
DOMAIN        = "ngingu.duvi.asia"
# ================================================

def has_sshpass():
    return shutil.which("sshpass") is not None

def run_ssh(label, remote_command):
    """Jalankan SSH command dengan label, exit jika gagal."""
    print(f"\n{'─'*60}")
    print(f"  {label}")
    print(f"{'─'*60}")

    if has_sshpass():
        base = [
            "sshpass", f"-p{VPS_PASSWORD}",
            "ssh",
            "-o", "StrictHostKeyChecking=no",
            "-o", "ConnectTimeout=30",
            f"-p{VPS_PORT}",
            f"{VPS_USER}@{VPS_IP}",
            remote_command,
        ]
    else:
        base = [
            "ssh",
            "-o", "StrictHostKeyChecking=no",
            "-o", "ConnectTimeout=30",
            f"-p{VPS_PORT}",
            f"{VPS_USER}@{VPS_IP}",
            remote_command,
        ]

    result = subprocess.run(base)
    if result.returncode != 0:
        print(f"\n[GAGAL] Langkah: {label}")
        sys.exit(result.returncode)

def check_deps():
    if not shutil.which("ssh"):
        print("[ERROR] 'ssh' tidak ditemukan. Install OpenSSH terlebih dahulu.")
        sys.exit(1)
    if not has_sshpass():
        print("[WARNING] 'sshpass' tidak ditemukan — kamu mungkin diminta password manual.")
        print("          Install: sudo apt install sshpass  (WSL/Ubuntu)\n")

def main():
    print("=" * 60)
    print("  Kaliwedi Backend — Deploy ke VPS")
    print("=" * 60)
    print(f"  Host : {VPS_IP}:{VPS_PORT}")
    print(f"  User : {VPS_USER}")
    print(f"  Dir  : {REMOTE_DIR}")
    print("=" * 60)

    check_deps()

    PATH_EXPORT = "export PATH=$PATH:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"

    steps = [
        (
            "1. Git fetch & reset ke origin/main",
            f"{PATH_EXPORT} && cd {REMOTE_DIR} && git fetch --all && git reset --hard origin/main",
        ),
        (
            "2. Stop & remove container lama",
            f"{PATH_EXPORT} && (docker stop kaliwedi-web 2>/dev/null || true) && (docker rm -f kaliwedi-web 2>/dev/null || true)",
        ),
        (
            "3. Docker Compose rebuild & restart",
            f"{PATH_EXPORT} && cd {REMOTE_DIR} && docker compose up -d --build",
        ),
        (
            "4. Tunggu container siap (10 detik)",
            "sleep 10",
        ),
        (
            "5. Artisan migrate --force",
            f"{PATH_EXPORT} && docker exec -t {CONTAINER} php artisan migrate --force",
        ),
        (
            "6. Artisan optimize:clear",
            f"{PATH_EXPORT} && docker exec -t {CONTAINER} php artisan optimize:clear",
        ),
        (
            "7. Artisan config:cache",
            f"{PATH_EXPORT} && docker exec -t {CONTAINER} php artisan config:cache",
        ),
        (
            "8. Artisan route:cache",
            f"{PATH_EXPORT} && docker exec -t {CONTAINER} php artisan route:cache",
        ),
        (
            "9. Artisan view:cache",
            f"{PATH_EXPORT} && docker exec -t {CONTAINER} php artisan view:cache",
        ),
        (
            "10. Cek status container",
            f"{PATH_EXPORT} && docker ps --filter name={CONTAINER} --format 'table {{{{.Names}}}}\t{{{{.Status}}}}\t{{{{.Ports}}}}'",
        ),
    ]

    for label, cmd in steps:
        run_ssh(label, cmd)

    print("\n" + "=" * 60)
    print("  Deploy selesai! Kaliwedi Backend sudah live.")
    print("=" * 60)

if __name__ == "__main__":
    main()
