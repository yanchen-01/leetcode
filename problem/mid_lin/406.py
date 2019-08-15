class Solution:
    """
    @param nums: an array of integers
    @param s: An integer
    @return: an integer representing the minimum size of subarray
    """
    window={0:0}
    length=[]
    l_index=0
    r_index=0
    minLength_key={}

    def minimumSize(self,nums, s):
        for k in range(len(nums)):
            if(nums[k]>=s):
                return 1

            tmp_window=self.window[self.l_index]
            self.window[self.l_index]+=nums[k]

            if(tmp_window+nums[k] >= s):
                self.length.append(self.r_index-self.l_index+1)
                for i in range(self.l_index+1, self.r_index+1):
                    self.window[i]= self.window[i-1]-nums[i-1]
                    self.l_index+=1
                    if(self.window[i]<s):
						break
                    else:
                        self.length.append(self.r_index-i+1)

            self.r_index+=1

        if(len(self.length)==0):
            return -1

        return min(self.length)


obj = Solution()
nums=[1,2,3,4,5,6,1,1,1,1]
s=15
rs=obj.minimumSize(nums,s)

print(rs)