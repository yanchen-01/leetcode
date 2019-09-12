# The data is provided via a class DataSimulator
import time
import os
import DataSimulator as DSim
import ECC
import hashlib

DS = DSim.DataSimulator()


class blockchian:

    block={}
    tree=[]
    hash_table={}
    sample={
          "msg":"cabinet meets to balance budget priorities",
          "pk":"Curve( 463 -2 2 ); G( 155 452 ); PK( 263 231 ); PKOrder( 149 )",
          "signature":[9,30]
       }
    #difficult="0000000000000000"
    difficult="0000"
    nonce=0

    def start(self):
        for i in range(6):
            # init data
            self.tree=[0]*300
            self.hash_table={}

            # get data
            data = DS.getNewData()

            print(len(data))

            # validate data
            data = self.validate_signature(data)

            print(len(data))
            exit()

            # build tree
            self.buildMerkleTree(data,self.tree,0,0,len(data)-1)

            # proof of work
            hash = self.proof_of_work()

            if(i==0):
                print(self.block)
                exit()

            time.sleep(1)


    def proofMerkleTree(self):
        return True 

    # ps aux|grep start.py
    # kill
    def proof_of_work(self):
        success = False
        while success is not True:
            data=str(self.nonce)+self.tree[0]
            hash = ECC.hash(data)
            self.nonce+=1
            print(self.nonce)

            if hash[:4] == self.difficult:
                success=True
    
        return hash

    def validate_signature(self,data):
        for v in data:
            check=(ECC.verify(v['pk'], v["msg"], v["signature"]))
            if check is not True:
                data.remove(v)

        return data

    def buildMerkleTree(self,data,tree,node,start,end):
        if (start == end):
            hash=ECC.hash(str(data[start]))
            self.hash_table[hash]=data[start]
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
        
obj = blockchian()
obj.start()