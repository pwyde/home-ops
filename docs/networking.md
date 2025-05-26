# Networking

## UniFi Cloud Gateway

### SSL Certificate

A semi-official third party [script](https://community.ui.com/questions/UniFi-Installation-Scripts-or-UniFi-Easy-Update-Script-or-UniFi-Lets-Encrypt-or-UniFi-Easy-Encrypt-/ccbc7530-dd61-40a7-82ec-22b17f027776) from Ubiquiti employee [Glenn R](https://glennr.nl/) can be used to secure UniFi applications and console with SSL certificate from **Let's Encrypt**.

Prior to installation create a `CNAME` record that points to the UCG. This is needed so that hostname resolution and validation can be performed by the script.

The script uses `keytool` and is at the time of writing not pre-installed on the UCG. SSH into the device and install the `default-jdk-headless`package.

```
root@Cloud-Gateway-Fiber:~# apt install default-jdk-headless
Reading package lists... Done
Building dependency tree... Done
Reading state information... Done
The following additional packages will be installed:
ca-certificates-java default-jre-headless fontconfig-config fonts-dejavu-core java-common libcups2 libfontconfig1 libgraphite2-3 libharfbuzz0b
libjpeg62-turbo liblcms2-2 libpcsclite1 openjdk-11-jdk-headless openjdk-11-jre-headless
Suggested packages:
default-jre cups-common liblcms2-utils pcscd openjdk-11-demo openjdk-11-source libnss-mdns fonts-dejavu-extra fonts-ipafont-gothic fonts-ipafont-mincho
fonts-wqy-microhei | fonts-wqy-zenhei fonts-indic
The following NEW packages will be installed:
ca-certificates-java default-jdk-headless default-jre-headless fontconfig-config fonts-dejavu-core java-common libcups2 libfontconfig1 libgraphite2-3
libharfbuzz0b libjpeg62-turbo liblcms2-2 libpcsclite1 openjdk-11-jdk-headless openjdk-11-jre-headless
0 upgraded, 15 newly installed, 0 to remove and 28 not upgraded.
Need to get 114 MB of archives.
After this operation, 262 MB of additional disk space will be used.
Do you want to continue? [Y/n]
```

Download and install the script.

```sh
mkdir -p ~/.local/bin
cd ~/.local/bin
curl -sO https://get.glennr.nl/unifi/extra/unifi-easy-encrypt.sh && chmod 755 unifi-easy-encrypt.sh
```

Create a secret credentials file with API credentials to the DNS provider.

```sh
mkdir -p ~/.secrets/EUS
touch ~/.secrets/EUS/cloudflare.ini
chmod 600 ~/.secrets/EUS/cloudflare.ini
```

Example credentials file using restricted API Token.

```
# Cloudflare API token used by Certbot
dns_cloudflare_api_token = 0123456789abcdef0123456789abcdef01234567
```

Execute script with the following arguments.

```sh
bash ~/.local/bin/unifi-easy-encrypt.sh --email ${SECRET_EMAIL} --fqdn unifi.${SECRET_DOMAIN} --dns-challenge --dns-provider cloudflare --dns-provider-credentials /root/.secrets/EUS/cloudflare.ini --external-dns 1.1.1.1 --prevent-modify-firewall --skip
```

Script options used:
- `--fqdn`: Specify the hostname of the UCG. This will be used by the generated Let's Encrypt certificate.
- `--email`: E-mail used for Let's Encrypt renewal notifications.
- `--external-dns`: Use specified DNS server for hostname validation and resolution instead of public DNS server, for example `1.1.1.1`.
- `--prevent-modify-firewall`: Do not automatically open/close port `80` on UniFi Gateway Consoles.
- `--skip`: Skip any kind of manual input. This automates the entire process.

### BGP Configuration for Cilium

**BGP** is not officially supported on the UCG-Fiber. The **FRR** package is however included on the device but not enabled.

SSH into the device and enable the BGP daemon by modifying the FRRouting (FRR) daemons configuration.

```sh
sed -i 's/bgpd=no/bgpd=yes/g' /etc/frr/daemons
```

Adjust the FRR configuration and define the BGP neighbors by editing the `/etc/frr/frr.conf` configuration file.

```
log syslog informational

! -*- bgp -*-
!
hostname gateway
frr defaults traditional
log file stdout
!
router bgp 64513
  bgp router-id 192.168.1.1
  no bgp ebgp-requires-policy
  !
  ! Peer group for Cilium
  neighbor k8s peer-group
  neighbor k8s remote-as 64514
  ! Neighbors for Cilium
  neighbor 192.168.20.21 peer-group k8s
  neighbor 192.168.20.22 peer-group k8s
  neighbor 192.168.20.23 peer-group k8s
  neighbor 192.168.20.24 peer-group k8s
  neighbor 192.168.20.25 peer-group k8s
  !
  address-family ipv4 unicast
    redistribute connected
    neighbor k8s next-hop-self
    neighbor k8s soft-reconfiguration inbound
  exit-address-family
  !
line vty
```

Enable and start the FRR service to apply the configuration and activate BGP routing.

```sh
systemctl enable frr.service && systemctl start frr.service
```

Verify BGP configuration Verify BGP configuration with the command `vtysh -c 'show ip bgp'`.
