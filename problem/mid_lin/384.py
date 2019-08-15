class Solution:
    """
    @param nums: an array of integers
    @param s: An integer
    @return: an integer representing the minimum size of subarray
    """
    def lengthOfLongestSubstring(self, s):
        b_index=0
        f_index=0
        pool=[]
        length=[]

        if(s==""):
            return 0

        for v in s:
            if v not in pool:
                pool.append(v)
                length.append(f_index-b_index+1)
            else:
                pool.append(v)
                if s[b_index]!=v:
                    while(s[b_index]!=v):
                        del pool[0]
                        b_index+=1
                del pool[0]
                b_index+=1

                length.append(f_index-b_index+1)

            f_index+=1  

        return max(length)


obj = Solution()
s="bbbbb"
rs=obj.lengthOfLongestSubstring(s)

print(rs)