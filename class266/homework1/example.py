import hashlib
y = 1
found = 0
while found==0:
    hh = hashlib.sha256(str(y).encode()).hexdigest()
    if hh[:4] == '0000':
        found=1
    y+=1

print(hh)
print(y)
