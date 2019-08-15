class Solution:
    """
    @param source : A string
    @param target: A string
    @return: A string denote the minimum window, return "" if there is no such a string
    """
    def minWindow(self, source , target):

        if(target==""):
            return ""

        count_s=[0] * 256
        count_t=[0] * 256
        c=0
        k=0
        l=0
        r=0
        rs_l=-1
        rs_r=-1

        for i in target:
            count_t[ord(i)]+=1
            if(count_t[ord(i)]==1):
                k+=1
                
        for l in range (len(source)):
            while(r<len(source) and c<k):
                count_s[ord(source[r])]+=1
                if(count_s[ord(source[r])]==count_t[ord(source[r])]):
                    c+=1
                r+=1

            if(k==c):
                if(rs_l==-1 or rs_r-rs_l>r-l):
                    rs_l=l
                    rs_r=r

            count_s[ord(source[l])]-=1
            if(count_s[ord(source[l])]==count_t[ord(source[l])]-1):
                    c-=1

        if(rs_l==-1):
            return ""


        return source[rs_l:rs_r]

        


obj = Solution()
source = "abc"
target = "ac"
rs=obj.minWindow(source,target)
print(rs)