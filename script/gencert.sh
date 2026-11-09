#!/bin/bash
if [ -z  "$1" ]; then
  echo -e "Error missing FQDN\n\nUsage: \n$0 example.com\n"
  exit 0
fi

_FQDN="$1"

# generat key
openssl genpkey -algorithm ED25519 > "${_FQDN}".key

# generate config
cat <<EOF > openssl-25519.cnf
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req
prompt = no
[req_distinguished_name]
C = DE
CN = ${_FQDN}
[v3_req]
keyUsage = keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names
[alt_names]
DNS.1 = ${HOSTNAME}
DNS.2 = localhost
EOF

# genetate request
openssl req -new -out "${_FQDN}".csr -key "${_FQDN}".key -config openssl-25519.cnf

# sign cert
openssl x509 -req -days 5475 -in "${_FQDN}".csr -signkey "${_FQDN}".key -out "${_FQDN}".pem

# print cert
openssl x509 -in "${_FQDN}".pem -text