#!/bin/bash
set -euo pipefail

REPO_URL="https://github.com/gjf54/weibulla-gnedenko-model"
BRANCH="main"
PROJECT_DIR="/opt/weibull"

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'
info() { echo -e "${GREEN}[INFO]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1" >&2; exit 1; }


if [ "$EUID" -ne 0 ]; then
    error "Please run as root (use sudo)."
fi

if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    error "Cannot detect OS."
fi

install_base_packages() {
    info "Installing base packages (make, git, curl)..."
    case $OS in
        ubuntu|debian)
            apt-get update -y
            apt-get install -y make git curl ca-certificates
            ;;
        centos|rhel|rocky|almalinux|fedora)
            if command -v dnf &>/dev/null; then
                dnf install -y make git curl ca-certificates
            else
                yum install -y make git curl ca-certificates
            fi
            ;;
        *)
            error "Unsupported OS: $OS"
            ;;
    esac
}

install_docker() {
    if command -v docker &>/dev/null; then
        info "Docker already installed."
        return
    fi
    info "Installing Docker..."
    curl -fsSL https://get.docker.com | bash
    systemctl enable --now docker
}

install_docker_compose() {
    if docker compose version &>/dev/null; then
        info "Docker Compose already available."
        return
    fi
    info "Installing Docker Compose V2..."
    DOCKER_CONFIG=${DOCKER_CONFIG:-/usr/local/lib/docker/cli-plugins}
    mkdir -p "$DOCKER_CONFIG"
    curl -SL "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o "$DOCKER_CONFIG/docker-compose"
    chmod +x "$DOCKER_CONFIG/docker-compose"
}

clone_repo() {
    if [ -d "$PROJECT_DIR" ]; then
        info "Project directory already exists. Pulling latest changes..."
        cd "$PROJECT_DIR"
        git pull origin "$BRANCH"
    else
        info "Cloning repository..."
        git clone --branch "$BRANCH" "$REPO_URL" "$PROJECT_DIR"
        cd "$PROJECT_DIR"
    fi
}

run_make_init() {
    if ! command -v make &>/dev/null; then
        error "Make not found (should have been installed)."
    fi
    info "Running 'make init'..."
    make init
}

get_server_ip() {
    if command -v curl &>/dev/null; then
        IP=$(curl -s --max-time 2 ifconfig.me 2>/dev/null || true)
    fi
    if [ -z "$IP" ] && command -v wget &>/dev/null; then
        IP=$(wget -qO- --timeout=2 ifconfig.me 2>/dev/null || true)
    fi
    if [ -z "$IP" ]; then
        IP=$(hostname -I | awk '{print $1}')
    fi
    echo "$IP"
}

main() {
    info "Starting bootstrap for $REPO_URL on $OS"
    install_base_packages
    install_docker
    install_docker_compose
    clone_repo
    run_make_init
    info "Install completed"
    info "Project is ready at $PROJECT_DIR"
    info "URL: http://${SERVER_IP}:${APP_PORT}"
}

SERVER_IP=$(get_server_ip)
APP_PORT=8090

main