class Solution:
    def twoSum(self, nums, target):
        l=0
        r=len(nums)-1
        s_nums=nums
        s_nums=sorted(nums)

        while(l<=r):
            if(s_nums[l]+s_nums[r]==target):
                rs_l=nums.index(s_nums[l])
                rs_r=nums.index(s_nums[r])

                if(rs_l == rs_r):
                    del nums[rs_l]
                    rs_r=nums.index(s_nums[l])
                    return [rs_l,rs_r+1]
                else:
                    return [rs_l,rs_r]

            elif(s_nums[l]+s_nums[r]<target):
                l+=1

            elif(s_nums[l]+s_nums[r]>target):
                r-=1

        return []




obj = Solution()
nums = [3,1,2,3]
target = 6
rs=obj.twoSum(nums,target)
print(rs)