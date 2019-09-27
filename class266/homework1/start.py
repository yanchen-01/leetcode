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
    block_num=0
    tree=[]
    tree_proof=[]

    hash_block={}
    hash_code={}
    hash_msg={}
    hash_tree={}
    sample="cabinet meets to balance budget priorities"
    difficult="0000"

    def start(self):
        print("=========build blockchain=========")
        for i in range(6):
            # init data
            self.init_data(i)

            # get data
            data = DS.getNewData()

            # validate data
            data = self.validate_signature(data)

            # build tree
            self.buildMerkleTree(data,self.tree,0,0,len(data)-1)
            self.hash_tree[i]=self.tree

            # proof of work
            self.proof_of_work()

            # build block
            self.build_block()

            time.sleep(1)

            print(self.block[i])
            #break
        print("\n")

        # proof
        self.proof()

    # init data
    def init_data(self,i):
        self.tree=[0]*1000
        self.nonce=0
        self.block_num=i
        if not self.pre_hash:
            self.pre_hash="0000"*8
        else:
            self.pre_hash=self.hash

    # find path

    # validate data
    def validate_signature(self,data):
        for v in data[:]:
            check=(ECC.verify(v['pk'], v["msg"], v["signature"]))
            if check is not True:
                data.remove(v)
            else:
                self.hash_msg[v["msg"]]={'block_num':self.block_num,'data':v}

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
            data=str(self.nonce)+self.tree[0]+self.pre_hash
            self.hash = ECC.hash(data)
            if self.hash[:len(self.difficult)] == self.difficult:
                success=True
                break
            self.nonce+=1
    
        return hash

    # build block
    def build_block(self):
        rs_block={'pre_hash':self.pre_hash,'nonce':self.nonce,'merkle_tree':self.tree[0]}
        self.block.append(rs_block)

    # proof
    def proof(self):
        if self.sample in self.hash_msg:
            # proof of merkle_tree
            data=self.hash_msg[self.sample]
            hash=ECC.hash(str(data['data']))
            tree=self.hash_tree[data['block_num']]
            tree_hash=self.find_tree_path(tree,hash)

            print("=========merkle tree proof=========")
            print("message => "+str(self.sample))
            print("tree_leef => "+str(hash))
            for v in self.tree_proof:
                print(v)
            print("tree_root => "+str(tree_hash))
            print("\n")

            # proof block
            print("=========block chain route=========")
            pre_hash=ECC.hash(str(self.block[data['block_num']]['nonce'])+tree_hash+self.block[data['block_num']]['pre_hash'])
            print("pre_hash => "+str(pre_hash))
            for i in range(data['block_num']+1,6):
                print(pre_hash+" => "+str(self.block[i]))
                pre_hash=ECC.hash(str(self.block[i]['nonce'])+self.block[i]['merkle_tree']+self.block[i]['pre_hash'])

            exit()
        else:
            print("false")

    def find_tree_path(self,tree,hash):
        
        node_index=tree.index(hash)
        node=tree[node_index]

        if node_index==0:
            return node

        # even
        if(node_index % 2) == 0:
            pair=tree[node_index-1]
            parent=ECC.hash(str(pair + node))
            self.tree_proof.append({"pair":pair,'node':node})

        # odd
        else:
            pair=tree[node_index+1]
            parent=ECC.hash(str(node + pair))
            self.tree_proof.append({"node":node,'pair':pair})

        return self.find_tree_path(tree,parent)


obj = blockchian()
obj.start()