# The data is provided via a class DataSimulator
import time
import os
import DataSimulator as DSim
import ECC
import hashlib

DS = DSim.DataSimulator()


class blockchian:

    block=[]
    nonce=0
    hash=""
    pre_hash=""

    tree=[]
    hash_code={}
    hash_msg={}
    sample="cabinet meets to balance budget priorities"
    difficult="0000"

    def start(self):
        for i in range(6):
            # init data
            self.init_data()

            # get data
            data = DS.getNewData()

            # validate data
            data = self.validate_signature(data)

            # build tree
            self.buildMerkleTree(data,self.tree,0,0,len(data)-1)

            # proof of work
            self.proof_of_work()

            # build block
            self.build_block()

            time.sleep(1)

            # debug
            print(self.block[i])

        # proof of merkle_tree
        self.proofMerkleTree()

    # init data
    def init_data(self):
        self.tree=[0]*1000
        self.nonce=0
        if not self.pre_hash:
            self.pre_hash="0000"*8
        else:
            self.pre_hash=self.hash

    # validate data
    def validate_signature(self,data):
        for v in data[:]:
            check=(ECC.verify(v['pk'], v["msg"], v["signature"]))
            if check is not True:
                data.remove(v)
            else:
                self.hash_msg[v["msg"]]=v

        return data

    # build tree
    def buildMerkleTree(self,data,tree,node,start,end):
        if (start == end):
            hash=ECC.hash(str(data[start]))
            self.hash_code[hash]=data[start]
            self.tree[node]=hash;	
            return hash
        else:
            mid=(start+end)/2
            lef_node=(2*node)+1
            rig_node=(2*node)+2
            
            lefHash=self.buildMerkleTree(data,self.tree,lef_node,start,mid)
            rigHash=self.buildMerkleTree(data,self.tree,rig_node,mid+1,end)
            hash=ECC.hash(str(lefHash + rigHash))
            self.tree[node]=hash;	

            return hash

    # ps aux|grep start.py
    # kill
    # proof of work
    def proof_of_work(self):
        success = False
        while success is not True:
            data=str(self.nonce)+self.tree[0]
            self.hash = ECC.hash(data)
            self.nonce+=1
            if self.hash[:len(self.difficult)] == self.difficult:
                success=True
    
        return hash

    # build block
    def build_block(self):
        rs_block={'pre_hash':self.pre_hash,'nonce':self.nonce,'merkle_tree':self.tree[0],'hash':self.hash}
        self.block.append(rs_block)

    # proof of merkle_tree
    def proofMerkleTree(self):
        if self.sample in self.hash_msg:
            print(self.hash_msg[self.sample])
        else:
            print("false")

        return True

obj = blockchian()
obj.start()